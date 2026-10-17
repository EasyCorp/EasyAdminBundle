<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\DefaultCrudTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\DefaultCrudTestEntityFactory;
use Symfony\Component\DomCrawler\Crawler;
use function Zenstruck\Foundry\Persistence\delete;

/**
 * Tests for default delete functionality in EasyAdmin CRUD operations.
 */
class DeleteTest extends AbstractCrudTestCase
{
    /** @var EntityRepository<DefaultCrudTestEntity> */
    protected EntityRepository $repository;

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
    }

    public function testDeleteActionExistsOnIndexPage(): void
    {
        $entity = DefaultCrudTestEntityFactory::createOne();

        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertIndexEntityActionExists(Action::DELETE, $entity->getId());
    }

    public function testDeleteActionExistsOnDetailPage(): void
    {
        $entity = DefaultCrudTestEntityFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for delete action on detail page
        $deleteAction = $crawler->filter('.action-'.Action::DELETE);
        $this->assertCount(1, $deleteAction, 'Delete action should be available on detail page');
    }

    public function testDeleteActionRemovesEntity(): void
    {
        $entityId = DefaultCrudTestEntityFactory::createOne()->getId();

        // get the index page to access delete action
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();
        $this->assertIndexEntityActionExists(Action::DELETE, $entityId);

        $this->submitDeleteAction($crawler, $entityId);

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $this->assertSame(0, $this->repository->count([]), 'The entity should have been deleted');
    }

    public function testEntityNoLongerExistsInDatabaseAfterDelete(): void
    {
        $entityId = DefaultCrudTestEntityFactory::createOne(['name' => 'Entity To Delete'])->getId();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        $this->submitDeleteAction($crawler, $entityId);

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify entity no longer exists
        $this->entityManager->clear();
        $deletedEntity = $this->repository->find($entityId);
        $this->assertNull($deletedEntity, 'Entity should no longer exist in database');

        // also verify by name
        $deletedEntityByName = $this->repository->findOneBy(['name' => 'Entity To Delete']);
        $this->assertNull($deletedEntityByName, 'Entity should not be found by name after deletion');
    }

    public function testRedirectAfterSuccessfulDeletion(): void
    {
        $entityId = DefaultCrudTestEntityFactory::createOne()->getId();

        // disable redirect following to check the redirect
        $this->client->followRedirects(false);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        $this->submitDeleteAction($crawler, $entityId);

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
        $entityId = DefaultCrudTestEntityFactory::createOne()->getId();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entityId));
        $this->assertResponseIsSuccessful();

        $this->submitDeleteAction($crawler, $entityId);

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $this->assertSame(0, $this->repository->count([]), 'Entity should be deleted from detail page');
    }

    public function testDeleteWithInvalidCsrfTokenFails(): void
    {
        $entityId = DefaultCrudTestEntityFactory::createOne()->getId();

        $this->client->request('POST', $this->getCrudUrl(Action::DELETE, $entityId), [
            'token' => 'invalid-csrf-token',
        ]);

        // clear entity manager and verify entity still exists
        $this->entityManager->clear();
        $this->assertSame(1, $this->repository->count([]), 'Entity should not be deleted with invalid CSRF token');
        $this->assertNotNull($this->repository->find($entityId), 'Entity should still exist after failed delete');
    }

    public function testDeleteNonExistentEntityReturns404(): void
    {
        $this->client->catchExceptions(true);

        $deletedEntity = DefaultCrudTestEntityFactory::createOne();
        $deletedEntityId = $deletedEntity->getId();
        delete($deletedEntity);

        // get a valid CSRF token first
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->submitDeleteAction($crawler, $deletedEntityId);

        $this->assertResponseStatusCodeSame(404);
        $this->assertSelectorTextContains('.error-message', 'This item is no longer available.');
    }

    public function testDeleteButtonHasCsrfToken(): void
    {
        $entity = DefaultCrudTestEntityFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertIndexEntityActionExists(Action::DELETE, $entity->getId());

        // the delete action submits a hidden form that holds the CSRF token
        $tokenField = $crawler->filter('#action-confirmation-form input[name="token"]');
        $this->assertCount(1, $tokenField, 'Delete form should have CSRF token');
        $this->assertNotEmpty($tokenField->attr('value'), 'CSRF token should have a value');
    }

    public function testDeleteOnlyAffectsTargetEntity(): void
    {
        $entity1Id = DefaultCrudTestEntityFactory::createOne()->getId();
        $entity2Id = DefaultCrudTestEntityFactory::createOne()->getId();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        $this->submitDeleteAction($crawler, $entity1Id);

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();

        $this->assertNull($this->repository->find($entity1Id), 'Deleted entity should not exist');
        $this->assertNotNull($this->repository->find($entity2Id), 'Other entity should still exist');
        $this->assertSame(1, $this->repository->count([]), 'Only one entity should be deleted');
    }

    private function submitDeleteAction(Crawler $crawler, int $entityId): void
    {
        $tokenField = $crawler->filter('#action-confirmation-form input[name="token"]');
        $this->assertCount(1, $tokenField, 'The page should include the form that holds the CSRF token of the delete action');

        $this->client->request('POST', $this->getCrudUrl(Action::DELETE, $entityId), [
            'token' => $tokenField->attr('value'),
        ]);
    }
}
