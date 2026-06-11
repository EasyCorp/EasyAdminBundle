<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\DefaultCrudTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;

/**
 * Tests for default detail page behavior in EasyAdmin CRUD operations.
 */
class DetailTest extends AbstractCrudTestCase
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
     * Creates a test entity specifically for detail tests.
     *
     * @param bool $trackForCleanup If true, entity will be deleted in tearDown
     */
    private function createTestEntity(bool $trackForCleanup = false): DefaultCrudTestEntity
    {
        $entity = new DefaultCrudTestEntity();
        $entity->setName('Detail Test Item '.uniqid());
        $entity->setDescription('This entity is for detail tests');
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

    /**
     * Ensures an entity with active=true exists for tests that need it.
     */
    private function ensureActiveEntityExists(): DefaultCrudTestEntity
    {
        $entity = $this->repository->findOneBy(['active' => true]);
        if (null === $entity) {
            $entity = $this->createTestEntity(true);
        }

        return $entity;
    }

    /**
     * Ensures an entity with a specific name exists for tests that need it.
     */
    private function ensureNamedEntityExists(string $name): DefaultCrudTestEntity
    {
        $entity = $this->repository->findOneBy(['name' => $name]);
        if (null === $entity) {
            $entity = new DefaultCrudTestEntity();
            $entity->setName($name);
            $entity->setDescription('This entity is for detail tests');
            $entity->setActive(true);
            $entity->setPriority(1);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            $this->createdEntityIds[] = $entity->getId();
        }

        return $entity;
    }

    public function testDetailPageLoadsForEntity(): void
    {
        $entity = $this->ensureEntityExists();

        $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testDetailPageShowsFieldGroups(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for field groups (EasyAdmin displays fields in field-group containers)
        $this->assertSelectorExists('.content-body', 'Content body should be present');
        $this->assertSelectorExists('.field-group', 'Field groups should be present');
    }

    public function testAllFieldsAreDisplayed(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // get all field groups
        $fieldGroups = $crawler->filter('.content-body .field-group');
        $this->assertGreaterThan(0, $fieldGroups->count(), 'At least one field should be displayed');

        // verify field labels exist
        $fieldLabels = $crawler->filter('.field-label');
        $this->assertGreaterThan(0, $fieldLabels->count(), 'Field labels should be present');

        // verify field values exist
        $fieldValues = $crawler->filter('.field-value');
        $this->assertGreaterThan(0, $fieldValues->count(), 'Field values should be present');
    }

    public function testDetailPageShowsCorrectEntityData(): void
    {
        $entity = $this->ensureNamedEntityExists('CRUD Test Item 1');

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // the page content should contain the entity's name
        $this->assertSelectorTextContains('.content-body', 'CRUD Test Item 1');
    }

    public function testBackActionIsAvailable(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for back/index action
        $backAction = $crawler->filter('.action-'.Action::INDEX);
        $this->assertCount(1, $backAction, 'Back/Index action should be available');
    }

    public function testEditActionIsAvailable(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for edit action
        $editAction = $crawler->filter('.action-'.Action::EDIT);
        $this->assertCount(1, $editAction, 'Edit action should be available');
    }

    public function testDeleteActionIsAvailable(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for delete action
        $deleteAction = $crawler->filter('.action-'.Action::DELETE);
        $this->assertCount(1, $deleteAction, 'Delete action should be available');
    }

    public function testEditActionLinkWorks(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // click the edit action
        $editLink = $crawler->filter('.action-'.Action::EDIT);
        $this->assertCount(1, $editLink);

        $this->client->click($editLink->link());
        $this->assertResponseIsSuccessful();

        // verify we are on the edit form page
        $this->assertSelectorExists('form', 'Edit page should display a form');
    }

    public function testBackActionLinkWorks(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // click the back action
        $backLink = $crawler->filter('.action-'.Action::INDEX);
        $this->assertCount(1, $backLink);

        $this->client->click($backLink->link());
        $this->assertResponseIsSuccessful();

        // verify we are back on the index page
        $this->assertSelectorExists('.datagrid', 'Should be redirected to index page');
    }

    public function testDetailPageForNonExistentEntityReturns404(): void
    {
        $this->client->catchExceptions(true);

        $this->client->request('GET', $this->generateDetailUrl(999999));

        $this->assertResponseStatusCodeSame(404);
        $this->assertSelectorTextContains('.error-message', 'This item is no longer available.');
    }

    public function testIdFieldIsDisplayed(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // the ID value should be displayed on the page
        $pageContent = $crawler->filter('.content-body')->text();
        $this->assertStringContainsString((string) $entity->getId(), $pageContent);
    }

    public function testBooleanFieldIsDisplayedCorrectly(): void
    {
        // get an entity with active = true
        $entity = $this->ensureActiveEntityExists();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // boolean fields are typically displayed as Yes/No in EasyAdmin
        $pageContent = $crawler->filter('.content-body')->text();
        $this->assertStringContainsString('Yes', $pageContent);
    }
}
