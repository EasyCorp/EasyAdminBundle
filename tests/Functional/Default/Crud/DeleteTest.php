<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\DefaultCrudTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;

/**
 * Tests for default delete functionality in EasyAdmin CRUD operations.
 */
class DeleteTest extends AbstractCrudTestCase
{
    protected EntityRepository $repository;

    /** @var int[] IDs of entities created during tests that should be cleaned up */
    private array $createdEntityIds = [];

    protected function getControllerFqcn(): string
    {
        return DefaultCrudTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(DefaultCrudTestEntity::class);
        $this->createdEntityIds = [];
    }

    protected function tearDown(): void
    {
        // clean up any entities created during this test
        foreach ($this->createdEntityIds as $entityId) {
            $entity = $this->repository->find($entityId);
            if (null !== $entity) {
                $this->entityManager->remove($entity);
            }
        }
        if (!empty($this->createdEntityIds)) {
            $this->entityManager->flush();
        }

        parent::tearDown();
    }

    /**
     * Creates a test entity specifically for delete tests to avoid affecting other tests.
     *
     * @param bool $trackForCleanup If true, entity will be deleted in tearDown if not already deleted
     */
    private function createTestEntity(bool $trackForCleanup = false): DefaultCrudTestEntity
    {
        $entity = new DefaultCrudTestEntity();
        $entity->setName('Entity To Delete '.uniqid());
        $entity->setDescription('This entity will be deleted');
        $entity->setActive(true);
        $entity->setPriority(1);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        if ($trackForCleanup) {
            $this->createdEntityIds[] = $entity->getId();
        }

        return $entity;
    }

    /**
     * Ensures at least one entity exists for tests that need to read existing entities.
     */
    private function ensureEntityExists(): DefaultCrudTestEntity
    {
        $entity = $this->repository->findOneBy([]);
        if (null === $entity) {
            $entity = $this->createTestEntity(true);
        }

        return $entity;
    }

    public function testDeleteActionExistsOnIndexPage(): void
    {
        $entity = $this->ensureEntityExists();

        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertIndexEntityActionExists(Action::DELETE, $entity->getId());
    }

    public function testDeleteActionExistsOnDetailPage(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for delete action on detail page
        $deleteAction = $crawler->filter('.action-'.Action::DELETE);
        $this->assertCount(1, $deleteAction, 'Delete action should be available on detail page');
    }

    public function testDeleteActionRemovesEntity(): void
    {
        // create a specific entity for deletion
        $entity = $this->createTestEntity();
        $entityId = $entity->getId();

        $initialCount = \count($this->repository->findAll());

        // get the index page to access delete action
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        // find the delete form for this entity
        $deleteForm = $crawler->filter(sprintf('tr[data-id="%d"] form.action-delete', $entityId));

        if ($deleteForm->count() > 0) {
            // submit the delete form
            $form = $deleteForm->form();
            $this->client->submit($form);
        } else {
            // try clicking delete link (some configurations use links)
            $deleteLink = $crawler->filter(sprintf('tr[data-id="%d"] .action-delete', $entityId));
            $this->assertCount(1, $deleteLink, 'Delete action should exist for entity');

            // get delete URL and submit POST request with CSRF token
            $deleteUrl = $this->getCrudUrl(Action::DELETE, $entityId);
            $csrfToken = $this->getCsrfToken($crawler);

            $this->client->request('POST', $deleteUrl, [
                'token' => $csrfToken,
            ]);
        }

        $this->assertResponseIsSuccessful();

        // clear entity manager to ensure fresh data from database
        $this->entityManager->clear();

        // verify entity count decreased
        $finalCount = \count($this->repository->findAll());
        $this->assertEquals($initialCount - 1, $finalCount, 'One entity should have been deleted');
    }

    public function testEntityNoLongerExistsInDatabaseAfterDelete(): void
    {
        // create a specific entity for deletion
        $entity = $this->createTestEntity();
        $entityId = $entity->getId();
        $entityName = $entity->getName();

        // verify entity exists before deletion
        $this->entityManager->clear();
        $existingEntity = $this->repository->find($entityId);
        $this->assertNotNull($existingEntity, 'Entity should exist before deletion');

        // get the index page to access delete action
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        // find and submit the delete form
        $deleteForm = $crawler->filter(sprintf('tr[data-id="%d"] form.action-delete', $entityId));

        if ($deleteForm->count() > 0) {
            $form = $deleteForm->form();
            $this->client->submit($form);
        } else {
            $deleteUrl = $this->getCrudUrl(Action::DELETE, $entityId);
            $csrfToken = $this->getCsrfToken($crawler);

            $this->client->request('POST', $deleteUrl, [
                'token' => $csrfToken,
            ]);
        }

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify entity no longer exists
        $this->entityManager->clear();
        $deletedEntity = $this->repository->find($entityId);
        $this->assertNull($deletedEntity, 'Entity should no longer exist in database');

        // also verify by name
        $deletedEntityByName = $this->repository->findOneBy(['name' => $entityName]);
        $this->assertNull($deletedEntityByName, 'Entity should not be found by name after deletion');
    }

    public function testRedirectAfterSuccessfulDeletion(): void
    {
        // create a specific entity for deletion
        $entity = $this->createTestEntity();
        $entityId = $entity->getId();

        // disable redirect following to check the redirect
        $this->client->followRedirects(false);

        // get the index page to access delete action
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        // find and submit the delete form
        $deleteForm = $crawler->filter(sprintf('tr[data-id="%d"] form.action-delete', $entityId));

        if ($deleteForm->count() > 0) {
            $form = $deleteForm->form();
            $this->client->submit($form);
        } else {
            $deleteUrl = $this->getCrudUrl(Action::DELETE, $entityId);
            $csrfToken = $this->getCsrfToken($crawler);

            $this->client->request('POST', $deleteUrl, [
                'token' => $csrfToken,
            ]);
        }

        // should redirect (302 or 303)
        $this->assertTrue(
            $this->client->getResponse()->isRedirect(),
            'Should redirect after successful deletion'
        );

        // follow redirect and verify we're on the index page
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.datagrid', 'Should be redirected to index page');
    }

    public function testDeleteFromDetailPage(): void
    {
        // create a specific entity for deletion
        $entity = $this->createTestEntity();
        $entityId = $entity->getId();

        $initialCount = \count($this->repository->findAll());

        // go to detail page
        $crawler = $this->client->request('GET', $this->generateDetailUrl($entityId));
        $this->assertResponseIsSuccessful();

        // find and submit the delete form on detail page
        $deleteForm = $crawler->filter('form.action-delete');

        if ($deleteForm->count() > 0) {
            $form = $deleteForm->form();
            $this->client->submit($form);
        } else {
            // try using delete link/button
            $deleteUrl = $this->getCrudUrl(Action::DELETE, $entityId);
            $csrfToken = $this->getCsrfToken($crawler);

            $this->client->request('POST', $deleteUrl, [
                'token' => $csrfToken,
            ]);
        }

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify deletion
        $this->entityManager->clear();
        $finalCount = \count($this->repository->findAll());
        $this->assertEquals($initialCount - 1, $finalCount, 'Entity should be deleted from detail page');
    }

    public function testDeleteWithInvalidCsrfTokenFails(): void
    {
        // create a specific entity for testing - track for cleanup since it won't be deleted
        $entity = $this->createTestEntity(true);
        $entityId = $entity->getId();

        $initialCount = \count($this->repository->findAll());

        // try to delete with invalid CSRF token
        $deleteUrl = $this->getCrudUrl(Action::DELETE, $entityId);

        $this->client->request('POST', $deleteUrl, [
            'token' => 'invalid-csrf-token',
        ]);

        // clear entity manager and verify entity still exists
        $this->entityManager->clear();
        $finalCount = \count($this->repository->findAll());
        $this->assertEquals($initialCount, $finalCount, 'Entity should not be deleted with invalid CSRF token');

        $existingEntity = $this->repository->find($entityId);
        $this->assertNotNull($existingEntity, 'Entity should still exist after failed delete');
    }

    public function testDeleteNonExistentEntityReturns404(): void
    {
        $this->client->catchExceptions(true);

        $deleteUrl = $this->getCrudUrl(Action::DELETE, 999999);

        // get a valid CSRF token first
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $csrfToken = $this->getCsrfToken($crawler);

        $this->client->request('POST', $deleteUrl, [
            'token' => $csrfToken,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteButtonHasCsrfToken(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that delete form has CSRF token
        $deleteForm = $crawler->filter(sprintf('tr[data-id="%d"] form.action-delete', $entity->getId()));

        if ($deleteForm->count() > 0) {
            $tokenField = $deleteForm->filter('input[name="token"]');
            $this->assertGreaterThan(0, $tokenField->count(), 'Delete form should have CSRF token');
            $this->assertNotEmpty($tokenField->attr('value'), 'CSRF token should have a value');
        }
    }

    public function testDeleteOnlyAffectsTargetEntity(): void
    {
        // create two entities - entity2 will be tracked for cleanup since it won't be deleted
        $entity1 = $this->createTestEntity();
        $entity2 = $this->createTestEntity(true);

        $entity1Id = $entity1->getId();
        $entity2Id = $entity2->getId();

        $initialCount = \count($this->repository->findAll());

        // delete entity1
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        $deleteForm = $crawler->filter(sprintf('tr[data-id="%d"] form.action-delete', $entity1Id));

        if ($deleteForm->count() > 0) {
            $form = $deleteForm->form();
            $this->client->submit($form);
        } else {
            $deleteUrl = $this->getCrudUrl(Action::DELETE, $entity1Id);
            $csrfToken = $this->getCsrfToken($crawler);

            $this->client->request('POST', $deleteUrl, [
                'token' => $csrfToken,
            ]);
        }

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify
        $this->entityManager->clear();

        // entity1 should be deleted
        $this->assertNull($this->repository->find($entity1Id), 'Deleted entity should not exist');

        // entity2 should still exist
        $this->assertNotNull($this->repository->find($entity2Id), 'Other entity should still exist');

        // total count should be decreased by 1
        $finalCount = \count($this->repository->findAll());
        $this->assertEquals($initialCount - 1, $finalCount, 'Only one entity should be deleted');
    }

    /**
     * Helper method to extract CSRF token from page.
     */
    private function getCsrfToken($crawler): string
    {
        // try to find CSRF token from delete form
        $tokenField = $crawler->filter('form.action-delete input[name="token"]');
        if ($tokenField->count() > 0) {
            return $tokenField->attr('value');
        }

        // try to find token from any form on the page
        $tokenField = $crawler->filter('input[name="token"]');
        if ($tokenField->count() > 0) {
            return $tokenField->first()->attr('value');
        }

        // try meta tag
        $metaToken = $crawler->filter('meta[name="csrf-token"]');
        if ($metaToken->count() > 0) {
            return $metaToken->attr('content');
        }

        return '';
    }
}
