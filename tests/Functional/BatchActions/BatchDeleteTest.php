<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\BatchActions;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\BatchActionTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\BatchActionTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BatchActionTestEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Kernel;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests batch delete functionality in EasyAdmin.
 */
class BatchDeleteTest extends AbstractCrudTestCase
{
    protected EntityRepository $repository;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return BatchActionTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(BatchActionTestEntity::class);
    }

    public function testIndexPageShowsBatchActionCheckboxes(): void
    {
        BatchActionTestEntityFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('.form-batch-checkbox-all', 'Select all checkbox should be present');
        self::assertCount(2, $crawler->filter('.form-batch-checkbox'), 'Each row should have its own checkbox');
    }

    public function testBatchDeleteActionButtonExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        // verify batch delete action exists in batch actions container
        $batchDeleteButton = $crawler->filter('.batch-actions [data-action-name="'.Action::BATCH_DELETE.'"]');
        self::assertCount(1, $batchDeleteButton, 'Batch delete action button should exist');
    }

    public function testBatchDeleteWithValidCsrfToken(): void
    {
        [$firstEntity, $secondEntity, $keptEntity] = BatchActionTestEntityFactory::createMany(3);
        $entityIdsToDelete = [$firstEntity->getId(), $secondEntity->getId()];

        $this->submitBatchDelete($entityIdsToDelete);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();

        self::assertSame(1, $this->repository->count([]), 'Two of the three entities should have been deleted');
        foreach ($entityIdsToDelete as $id) {
            self::assertNull($this->repository->find($id), sprintf('Entity with ID %s should have been deleted', $id));
        }
        self::assertNotNull($this->repository->find($keptEntity->getId()));
    }

    public function testBatchDeleteWithInvalidCsrfTokenDoesNotDelete(): void
    {
        $entityIdToDelete = BatchActionTestEntityFactory::createOne()->getId();

        $this->submitBatchDelete([$entityIdToDelete], useValidCsrfToken: false);

        $this->entityManager->clear();

        self::assertSame(1, $this->repository->count([]), 'No entities should have been deleted with invalid CSRF token');
        self::assertNotNull($this->repository->find($entityIdToDelete), 'Entity should still exist');
    }

    public function testBatchDeleteWithEmptySelectionDoesNothing(): void
    {
        BatchActionTestEntityFactory::createMany(2);

        $this->submitBatchDelete([]);

        $this->entityManager->clear();

        self::assertSame(2, $this->repository->count([]), 'No entities should have been deleted with empty selection');
    }

    public function testBatchDeleteWithNonExistentEntityId(): void
    {
        $entity = BatchActionTestEntityFactory::createOne();

        $this->submitBatchDelete([$entity->getId() + 1]);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();

        self::assertSame(1, $this->repository->count([]), 'No entities should have been deleted with non-existent ID');
        self::assertNotNull($this->repository->find($entity->getId()));
    }

    public function testBatchDeleteRejectsMismatchedEntityFqcn(): void
    {
        $categoryRepository = $this->entityManager->getRepository(Category::class);
        $targetCategoryId = CategoryFactory::createOne()->getId();
        BatchActionTestEntityFactory::createOne();

        // simulate an attacker that can batchDelete on BatchActionTestEntityCrudController
        // but targets a different Doctrine entity (Category) by overriding entityFqcn in
        // the POST body. The attack must be blocked (either by the FQCN-bound CSRF token
        // or by the explicit mismatch check) and no rows of any entity may be deleted.
        $this->submitBatchDelete([$targetCategoryId], entityFqcnOverride: Category::class);

        $this->entityManager->clear();

        self::assertNotNull(
            $categoryRepository->find($targetCategoryId),
            'Category row must not be deleted by a cross-entity batchDelete attempt'
        );
        self::assertSame(1, $categoryRepository->count([]));
        self::assertSame(1, $this->repository->count([]));
    }

    public function testBatchDeleteRedirectsToIndexPage(): void
    {
        $entityIdToDelete = BatchActionTestEntityFactory::createOne()->getId();

        $this->submitBatchDelete([$entityIdToDelete]);
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('.datagrid', 'Should be redirected to index page showing the datagrid');
        $this->entityManager->clear();
        self::assertNull($this->repository->find($entityIdToDelete));
    }

    /**
     * Simulates selecting checkboxes and clicking the batch delete button.
     *
     * EasyAdmin's batch actions work via JavaScript that dynamically creates a form
     * when the batch action button is clicked. Since Symfony functional tests don't
     * execute JavaScript, we simulate this by:
     * 1. Loading the index page and finding the batch delete button
     * 2. Extracting the data attributes needed for submission
     * 3. Submitting a POST request with the same parameters the JavaScript would send
     *
     * @param array<int|null> $entityIds          The entity IDs to delete
     * @param bool            $useValidCsrfToken  Whether to use a valid CSRF token (false to test CSRF protection)
     * @param string|null     $entityFqcnOverride When set, replaces the body's entityFqcn with this class (used to
     *                                            simulate a cross-entity authorization-bypass attempt)
     *
     * @return Crawler The crawler after submitting the batch action
     */
    private function submitBatchDelete(array $entityIds, bool $useValidCsrfToken = true, ?string $entityFqcnOverride = null): Crawler
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        // find the batch delete button and extract the data attributes needed for submission
        $batchDeleteButton = $crawler->filter('.batch-actions [data-action-name="'.Action::BATCH_DELETE.'"]');
        self::assertCount(1, $batchDeleteButton, 'Batch delete action button should exist');

        $csrfToken = $useValidCsrfToken ? $batchDeleteButton->attr('data-action-csrf-token') : 'invalid-csrf-token';
        $batchActionUrl = $batchDeleteButton->attr('data-action-url');
        $entityFqcn = $entityFqcnOverride ?? $batchDeleteButton->attr('data-entity-fqcn');

        // submit the batch delete action
        return $this->client->request('POST', $batchActionUrl, [
            'batchActionName' => Action::BATCH_DELETE,
            'entityFqcn' => $entityFqcn,
            'batchActionUrl' => $batchActionUrl,
            'batchActionCsrfToken' => $csrfToken,
            'batchActionEntityIds' => $entityIds,
        ]);
    }
}
