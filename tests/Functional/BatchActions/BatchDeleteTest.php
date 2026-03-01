<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\BatchActions;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\BatchActionTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\BatchActionTestEntity;
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
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        // verify the "select all" checkbox exists
        self::assertSelectorExists('.form-batch-checkbox-all', 'Select all checkbox should be present');

        // verify individual row checkboxes exist
        $checkboxes = $crawler->filter('.form-batch-checkbox');
        self::assertGreaterThan(0, $checkboxes->count(), 'Individual row checkboxes should be present');
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
        $initialCount = \count($this->repository->findAll());
        self::assertGreaterThanOrEqual(3, $initialCount, 'Need at least 3 entities to test batch delete');

        // get IDs of entities to delete (first 2 entities)
        $entities = $this->repository->findAll();
        $entityIdsToDelete = [
            $entities[0]->getId(),
            $entities[1]->getId(),
        ];

        $this->submitBatchDelete($entityIdsToDelete);
        self::assertResponseIsSuccessful();

        // clear entity manager to get fresh data
        $this->entityManager->clear();

        // verify entities were deleted
        $finalCount = \count($this->repository->findAll());
        self::assertSame($initialCount - 2, $finalCount, 'Two entities should have been deleted');

        // verify specific entities are gone
        foreach ($entityIdsToDelete as $id) {
            self::assertNull($this->repository->find($id), sprintf('Entity with ID %s should have been deleted', $id));
        }
    }

    public function testBatchDeleteWithInvalidCsrfTokenDoesNotDelete(): void
    {
        $initialCount = \count($this->repository->findAll());
        self::assertGreaterThanOrEqual(1, $initialCount, 'Need at least 1 entity to test');

        // get first entity ID
        $entities = $this->repository->findAll();
        $entityIdToDelete = $entities[0]->getId();

        $this->submitBatchDelete([$entityIdToDelete], useValidCsrfToken: false);

        // clear entity manager to get fresh data
        $this->entityManager->clear();

        // verify entity was NOT deleted (CSRF validation should fail)
        $finalCount = \count($this->repository->findAll());
        self::assertSame($initialCount, $finalCount, 'No entities should have been deleted with invalid CSRF token');
        self::assertNotNull($this->repository->find($entityIdToDelete), 'Entity should still exist');
    }

    public function testBatchDeleteWithEmptySelectionDoesNothing(): void
    {
        $initialCount = \count($this->repository->findAll());

        $this->submitBatchDelete([]);

        // clear entity manager to get fresh data
        $this->entityManager->clear();

        // verify no entities were deleted
        $finalCount = \count($this->repository->findAll());
        self::assertSame($initialCount, $finalCount, 'No entities should have been deleted with empty selection');
    }

    public function testBatchDeleteWithNonExistentEntityId(): void
    {
        $initialCount = \count($this->repository->findAll());

        $this->submitBatchDelete([999999]); // non-existent ID
        self::assertResponseIsSuccessful();

        // clear entity manager to get fresh data
        $this->entityManager->clear();

        // verify no entities were deleted
        $finalCount = \count($this->repository->findAll());
        self::assertSame($initialCount, $finalCount, 'No entities should have been deleted with non-existent ID');
    }

    public function testBatchDeleteRedirectsToIndexPage(): void
    {
        // get first entity ID
        $entities = $this->repository->findAll();
        $entityIdToDelete = $entities[0]->getId();

        $this->submitBatchDelete([$entityIdToDelete]);
        self::assertResponseIsSuccessful();

        // should be redirected back to index page - verify by checking for index elements
        self::assertSelectorExists('.datagrid', 'Should be redirected to index page showing the datagrid');
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
     * @param array $entityIds         The entity IDs to delete
     * @param bool  $useValidCsrfToken Whether to use a valid CSRF token (false to test CSRF protection)
     *
     * @return Crawler The crawler after submitting the batch action
     */
    private function submitBatchDelete(array $entityIds, bool $useValidCsrfToken = true): Crawler
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        // find the batch delete button and extract the data attributes needed for submission
        $batchDeleteButton = $crawler->filter('.batch-actions [data-action-name="'.Action::BATCH_DELETE.'"]');
        self::assertCount(1, $batchDeleteButton, 'Batch delete action button should exist');

        $csrfToken = $useValidCsrfToken ? $batchDeleteButton->attr('data-action-csrf-token') : 'invalid-csrf-token';
        $batchActionUrl = $batchDeleteButton->attr('data-action-url');
        $entityFqcn = $batchDeleteButton->attr('data-entity-fqcn');

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
