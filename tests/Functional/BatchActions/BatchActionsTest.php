<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\BatchActions;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\BatchActionTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\BatchActionTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BatchActionTestEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Kernel;
use Symfony\Component\DomCrawler\Crawler;

class BatchActionsTest extends AbstractCrudTestCase
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

    public function testCustomBatchActionsExistInIndex(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        // verify custom batch actions exist
        $batchActivateButton = $crawler->filter('.batch-actions [data-action-name="batchActivate"]');
        self::assertCount(1, $batchActivateButton, 'Batch activate action button should exist');

        $batchDeactivateButton = $crawler->filter('.batch-actions [data-action-name="batchDeactivate"]');
        self::assertCount(1, $batchDeactivateButton, 'Batch deactivate action button should exist');
    }

    public function testBatchActivateAction(): void
    {
        $inactiveEntities = BatchActionTestEntityFactory::createMany(2, ['active' => false, 'status' => null]);
        $entityIdsToActivate = array_map(static fn (BatchActionTestEntity $entity) => $entity->getId(), $inactiveEntities);

        $this->submitBatchAction('batchActivate', $entityIdsToActivate);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();

        foreach ($entityIdsToActivate as $id) {
            $entity = $this->repository->find($id);
            self::assertTrue($entity->isActive(), sprintf('Entity %s should be active after batch activate', $id));
            self::assertSame('activated', $entity->getStatus(), sprintf('Entity %s status should be "activated"', $id));
        }
    }

    public function testBatchDeactivateAction(): void
    {
        $activeEntities = BatchActionTestEntityFactory::createMany(2, ['active' => true, 'status' => null]);
        $entityIdsToDeactivate = array_map(static fn (BatchActionTestEntity $entity) => $entity->getId(), $activeEntities);

        $this->submitBatchAction('batchDeactivate', $entityIdsToDeactivate);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();

        foreach ($entityIdsToDeactivate as $id) {
            $entity = $this->repository->find($id);
            self::assertFalse($entity->isActive(), sprintf('Entity %s should be inactive after batch deactivate', $id));
            self::assertSame('deactivated', $entity->getStatus(), sprintf('Entity %s status should be "deactivated"', $id));
        }
    }

    public function testBatchActionShowsFlashMessage(): void
    {
        $inactiveEntity = BatchActionTestEntityFactory::createOne(['active' => false]);

        $crawler = $this->submitBatchAction('batchActivate', [$inactiveEntity->getId()]);
        self::assertResponseIsSuccessful();

        $flashMessages = $crawler->filter('.alert-success');
        self::assertGreaterThan(0, $flashMessages->count(), 'Success flash message should be displayed');
        self::assertStringContainsString('1 item(s) activated successfully', $flashMessages->text());
    }

    public function testBatchActionWithMultipleEntities(): void
    {
        $inactiveEntities = BatchActionTestEntityFactory::createMany(3, ['active' => false]);
        $entityIdsToActivate = array_map(static fn (BatchActionTestEntity $entity) => $entity->getId(), $inactiveEntities);

        $crawler = $this->submitBatchAction('batchActivate', $entityIdsToActivate);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();

        foreach ($entityIdsToActivate as $id) {
            $entity = $this->repository->find($id);
            self::assertTrue($entity->isActive(), sprintf('Entity %s should be active after batch activate', $id));
        }

        $flashMessages = $crawler->filter('.alert-success');
        self::assertStringContainsString('3 item(s)', $flashMessages->text());
    }

    public function testBatchActionsContainerIsInitiallyHidden(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        // the batch actions container should have d-none class (hidden by default)
        $batchActionsContainer = $crawler->filter('.batch-actions');
        self::assertStringContainsString('d-none', $batchActionsContainer->attr('class'), 'Batch actions should be hidden initially');
    }

    public function testCheckboxesHaveCorrectEntityIds(): void
    {
        $entities = BatchActionTestEntityFactory::createMany(3);
        $createdIds = array_map(static fn (BatchActionTestEntity $entity) => (string) $entity->getId(), $entities);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        $checkboxValues = $crawler->filter('.form-batch-checkbox')->each(static fn (Crawler $checkbox) => $checkbox->attr('value'));
        $rowEntityIds = $crawler->filter('table.datagrid tbody tr[data-id]')->each(static fn (Crawler $row) => $row->attr('data-id'));

        self::assertCount(\count($createdIds), $rowEntityIds, 'Each created entity should be rendered as a row');
        self::assertSame($rowEntityIds, $checkboxValues, 'Each row should have exactly one checkbox with its entity ID');

        sort($checkboxValues);
        sort($createdIds);
        self::assertSame($createdIds, $checkboxValues);
    }

    public function testBatchActionWithInvalidCsrfTokenFails(): void
    {
        $entityToTest = BatchActionTestEntityFactory::createOne(['active' => false, 'status' => 'pending']);
        $entityId = $entityToTest->getId();

        $this->submitBatchAction('batchActivate', [$entityId], useValidCsrfToken: false);

        $this->entityManager->clear();

        // the batch activate action changes status to 'activated', so if CSRF fails,
        // the status should remain unchanged
        $entity = $this->repository->find($entityId);
        self::assertSame('pending', $entity->getStatus(), 'Entity status should not be modified with invalid CSRF token');
        self::assertFalse($entity->isActive());
    }

    /**
     * Simulates selecting checkboxes and clicking a batch action button.
     *
     * EasyAdmin's batch actions work via JavaScript that dynamically creates a form
     * when the batch action button is clicked. Since Symfony functional tests don't
     * execute JavaScript, we simulate this by:
     * 1. Loading the index page and verifying the checkboxes exist
     * 2. Verifying the batch action button exists with proper data attributes
     * 3. Submitting a POST request with the same parameters the JavaScript would send
     *
     * @param string          $actionName        The batch action name (e.g., 'batchActivate')
     * @param array<int|null> $entityIds         The entity IDs to select and include in the batch action
     * @param bool            $useValidCsrfToken Whether to use a valid CSRF token (false to test CSRF protection)
     *
     * @return Crawler The crawler after submitting the batch action
     */
    private function submitBatchAction(string $actionName, array $entityIds, bool $useValidCsrfToken = true): Crawler
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        // verify that the checkboxes for the selected entities exist on the page
        foreach ($entityIds as $entityId) {
            $checkbox = $crawler->filter(sprintf('.form-batch-checkbox[value="%s"]', $entityId));
            self::assertCount(1, $checkbox, sprintf('Checkbox for entity %s should exist on the page', $entityId));
        }

        // find the batch action button and extract the data attributes needed for submission
        $batchActionButton = $crawler->filter(sprintf('.batch-actions [data-action-name="%s"]', $actionName));
        self::assertCount(1, $batchActionButton, sprintf('Batch action button "%s" should exist', $actionName));

        $csrfToken = $useValidCsrfToken ? $batchActionButton->attr('data-action-csrf-token') : 'invalid-csrf-token';
        $batchActionUrl = $batchActionButton->attr('data-action-url');
        $entityFqcn = $batchActionButton->attr('data-entity-fqcn');

        // submit the batch action (simulating what JavaScript does when the button is clicked)
        return $this->client->request('POST', $batchActionUrl, [
            'batchActionName' => $actionName,
            'entityFqcn' => $entityFqcn,
            'batchActionUrl' => $batchActionUrl,
            'batchActionCsrfToken' => $csrfToken,
            'batchActionEntityIds' => $entityIds,
        ]);
    }
}
