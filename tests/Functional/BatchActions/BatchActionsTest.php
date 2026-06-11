<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\BatchActions;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\BatchActionTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\BatchActionTestEntity;
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
        // find entities that are inactive (active = false)
        $inactiveEntities = $this->repository->findBy(['active' => false]);
        self::assertGreaterThanOrEqual(2, \count($inactiveEntities), 'Need at least 2 inactive entities to test');

        $entityIdsToActivate = [
            $inactiveEntities[0]->getId(),
            $inactiveEntities[1]->getId(),
        ];

        $this->submitBatchAction('batchActivate', $entityIdsToActivate);
        self::assertResponseIsSuccessful();

        // clear entity manager cache to get fresh data
        $this->entityManager->clear();

        // verify entities are now active
        foreach ($entityIdsToActivate as $id) {
            $entity = $this->repository->find($id);
            self::assertTrue($entity->isActive(), sprintf('Entity %s should be active after batch activate', $id));
            self::assertSame('activated', $entity->getStatus(), sprintf('Entity %s status should be "activated"', $id));
        }
    }

    public function testBatchDeactivateAction(): void
    {
        // find entities that are active (active = true)
        $activeEntities = $this->repository->findBy(['active' => true]);
        self::assertGreaterThanOrEqual(2, \count($activeEntities), 'Need at least 2 active entities to test');

        $entityIdsToDeactivate = [
            $activeEntities[0]->getId(),
            $activeEntities[1]->getId(),
        ];

        $this->submitBatchAction('batchDeactivate', $entityIdsToDeactivate);
        self::assertResponseIsSuccessful();

        // clear entity manager cache to get fresh data
        $this->entityManager->clear();

        // verify entities are now inactive
        foreach ($entityIdsToDeactivate as $id) {
            $entity = $this->repository->find($id);
            self::assertFalse($entity->isActive(), sprintf('Entity %s should be inactive after batch deactivate', $id));
            self::assertSame('deactivated', $entity->getStatus(), sprintf('Entity %s status should be "deactivated"', $id));
        }
    }

    public function testBatchActionShowsFlashMessage(): void
    {
        // find an inactive entity
        $inactiveEntities = $this->repository->findBy(['active' => false]);
        self::assertGreaterThanOrEqual(1, \count($inactiveEntities), 'Need at least 1 inactive entity to test');

        $entityIdToActivate = $inactiveEntities[0]->getId();

        $crawler = $this->submitBatchAction('batchActivate', [$entityIdToActivate]);
        self::assertResponseIsSuccessful();

        // verify flash message is displayed
        $flashMessages = $crawler->filter('.alert-success');
        self::assertGreaterThan(0, $flashMessages->count(), 'Success flash message should be displayed');
        self::assertStringContainsString('activated successfully', $flashMessages->text());
    }

    public function testBatchActionWithMultipleEntities(): void
    {
        // find multiple inactive entities
        $inactiveEntities = $this->repository->findBy(['active' => false]);
        self::assertGreaterThanOrEqual(3, \count($inactiveEntities), 'Need at least 3 inactive entities to test');

        $entityIdsToActivate = [
            $inactiveEntities[0]->getId(),
            $inactiveEntities[1]->getId(),
            $inactiveEntities[2]->getId(),
        ];

        $crawler = $this->submitBatchAction('batchActivate', $entityIdsToActivate);
        self::assertResponseIsSuccessful();

        // clear entity manager cache to get fresh data
        $this->entityManager->clear();

        // verify all 3 entities are now active
        foreach ($entityIdsToActivate as $id) {
            $entity = $this->repository->find($id);
            self::assertTrue($entity->isActive(), sprintf('Entity %s should be active after batch activate', $id));
        }

        // verify flash message mentions correct count
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
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        self::assertResponseIsSuccessful();

        // get all checkbox values from the page
        $checkboxes = $crawler->filter('.form-batch-checkbox');
        $checkboxValues = $checkboxes->each(static fn ($checkbox) => $checkbox->attr('value'));

        // get the entity IDs that should be displayed on the page (respecting pagination)
        $tableRows = $crawler->filter('table.datagrid tbody tr[data-id]');
        $expectedEntityIds = $tableRows->each(static fn ($row) => $row->attr('data-id'));

        self::assertGreaterThan(0, \count($checkboxValues), 'Should have entity checkboxes');
        self::assertCount(\count($expectedEntityIds), $checkboxValues, 'Each row should have exactly one checkbox');

        // verify each checkbox value matches the corresponding row's entity ID
        foreach ($checkboxValues as $index => $checkboxValue) {
            self::assertSame(
                $expectedEntityIds[$index],
                $checkboxValue,
                sprintf('Checkbox value should match the entity ID of row %s', $index)
            );
        }

        // also verify these IDs actually exist in the database
        foreach ($checkboxValues as $value) {
            $entity = $this->repository->find($value);
            self::assertNotNull($entity, sprintf('Entity with ID %s should exist in the database', $value));
        }
    }

    public function testBatchActionWithInvalidCsrfTokenFails(): void
    {
        // find any entity to test with
        $entities = $this->repository->findAll();
        self::assertGreaterThanOrEqual(1, \count($entities), 'Need at least 1 entity to test');

        $entityToTest = $entities[0];
        $entityId = $entityToTest->getId();
        $originalStatus = $entityToTest->getStatus();

        // submit batch activate form with invalid CSRF token
        $this->submitBatchAction('batchActivate', [$entityId], useValidCsrfToken: false);

        // clear entity manager cache to get fresh data
        $this->entityManager->clear();

        // verify entity status was NOT modified (CSRF validation should fail)
        // the batch activate action changes status to 'activated', so if CSRF fails,
        // the status should remain unchanged
        $entity = $this->repository->find($entityId);
        self::assertSame($originalStatus, $entity->getStatus(), 'Entity status should not be modified with invalid CSRF token');
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
     * @param string $actionName        The batch action name (e.g., 'batchActivate')
     * @param array  $entityIds         The entity IDs to select and include in the batch action
     * @param bool   $useValidCsrfToken Whether to use a valid CSRF token (false to test CSRF protection)
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
