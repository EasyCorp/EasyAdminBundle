<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\DefaultCrudTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;

/**
 * Tests for default edit form behavior in EasyAdmin CRUD operations.
 */
class EditTest extends AbstractCrudTestCase
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
     * Creates a test entity specifically for edit tests.
     *
     * @param bool $trackForCleanup If true, entity will be deleted in tearDown if not already deleted
     */
    private function createTestEntity(bool $trackForCleanup = false): DefaultCrudTestEntity
    {
        $entity = new DefaultCrudTestEntity();
        $entity->setName('Edit Test Entity '.uniqid());
        $entity->setDescription('This entity is for edit tests');
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
     * Ensures an entity with active = false exists for tests that need an inactive entity.
     */
    private function ensureInactiveEntityExists(): DefaultCrudTestEntity
    {
        $entity = $this->repository->findOneBy(['active' => false]);
        if (null === $entity) {
            $entity = new DefaultCrudTestEntity();
            $entity->setName('Inactive Edit Test Entity '.uniqid());
            $entity->setDescription('This entity is inactive');
            $entity->setActive(false);
            $entity->setPriority(1);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            $this->createdEntityIds[] = $entity->getId();
        }

        return $entity;
    }

    public function testEditFormLoadsWithExistingEntityData(): void
    {
        $entity = $this->ensureEntityExists();

        $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testEditFormDisplaysForm(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form', 'Edit page should display a form');
    }

    public function testEditFormShowsExistingValues(): void
    {
        // create a specific entity with known values for this test
        $entity = new DefaultCrudTestEntity();
        $entity->setName('Edit Form Values Test Entity');
        $entity->setDescription('Test description');
        $entity->setActive(true);
        $entity->setPriority(5);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->createdEntityIds[] = $entity->getId();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // get the form and check that it contains the existing values
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $this->assertEquals('Edit Form Values Test Entity', $form[$formName.'[name]']->getValue());
    }

    public function testEditFormHasRequiredFields(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check that expected form fields exist
        $this->assertFormFieldExists('name');
        $this->assertFormFieldExists('description');
        $this->assertFormFieldExists('active');
        $this->assertFormFieldExists('priority');
    }

    public function testFormCanBeSubmittedWithUpdatedData(): void
    {
        // create a specific entity for this test - track for cleanup
        $entity = $this->createTestEntity(true);
        $originalName = $entity->getName();
        $entityId = $entity->getId();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entityId));
        $this->assertResponseIsSuccessful();

        // get the form and update with new data
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $newName = 'Updated Name '.uniqid();
        $form[$formName.'[name]'] = $newName;

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify the update
        $this->entityManager->clear();
        $updatedEntity = $this->repository->find($entityId);

        $this->assertNotNull($updatedEntity);
        $this->assertEquals($newName, $updatedEntity->getName());
        $this->assertNotEquals($originalName, $updatedEntity->getName());
    }

    public function testEntityIsUpdatedInDatabase(): void
    {
        // create a specific entity for this test - track for cleanup
        $entity = new DefaultCrudTestEntity();
        $entity->setName('Entity To Update');
        $entity->setDescription('Original description');
        $entity->setActive(false);
        $entity->setPriority(10);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $entityId = $entity->getId();
        $this->createdEntityIds[] = $entityId;

        // clear entity manager
        $this->entityManager->clear();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entityId));
        $this->assertResponseIsSuccessful();

        // get the form and update all fields
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = 'Updated Entity Name';
        $form[$formName.'[description]'] = 'Updated description';
        $form[$formName.'[active]'] = '1';
        $form[$formName.'[priority]'] = '99';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify all updates
        $this->entityManager->clear();
        $updatedEntity = $this->repository->find($entityId);

        $this->assertNotNull($updatedEntity);
        $this->assertEquals('Updated Entity Name', $updatedEntity->getName());
        $this->assertEquals('Updated description', $updatedEntity->getDescription());
        $this->assertTrue($updatedEntity->isActive());
        $this->assertEquals(99, $updatedEntity->getPriority());
    }

    public function testRedirectAfterSuccessfulUpdate(): void
    {
        // create a specific entity for this test - track for cleanup
        $entity = $this->createTestEntity(true);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        $this->assertResponseIsSuccessful();

        // get the form and submit
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        // disable redirect following to check the redirect
        $this->client->followRedirects(false);
        $this->client->submit($form);

        // should redirect (302 or 303)
        $this->assertTrue(
            $this->client->getResponse()->isRedirect(),
            'Should redirect after successful update'
        );

        // follow redirect and verify we're on a success page
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testEditFormForNonExistentEntityReturns404(): void
    {
        $this->client->catchExceptions(true);

        $this->client->request('GET', $this->generateEditFormUrl(999999));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditFormHasFormElements(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check that the form has input fields (form is functional)
        $formInputs = $crawler->filter('form input, form textarea, form select');
        $this->assertGreaterThan(0, $formInputs->count(), 'Form should have input elements');
    }

    public function testEditFormHasCsrfToken(): void
    {
        $entity = $this->ensureEntityExists();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for CSRF token field
        $csrfField = $crawler->filter('form input[name*="[_token]"]');
        $this->assertGreaterThan(0, $csrfField->count(), 'Form should have CSRF token');
    }

    public function testEditFormFieldsCanBeModified(): void
    {
        $entity = $this->ensureEntityExists();
        $entityId = $entity->getId();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entityId));
        $this->assertResponseIsSuccessful();

        // verify form fields are editable
        $form = $crawler->filter($this->getEntityFormSelector())->form();
        $formName = $this->getFormEntity();

        // check that fields exist and can be modified
        $this->assertNotNull($form[$formName.'[name]'], 'Name field should exist');
        $this->assertNotNull($form[$formName.'[description]'], 'Description field should exist');
    }

    public function testEditDoesNotCreateNewEntity(): void
    {
        // create a specific entity for this test - track for cleanup
        $entity = $this->createTestEntity(true);

        $initialCount = \count($this->repository->findAll());

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        $this->assertResponseIsSuccessful();

        // get the form and submit
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = 'Modified Name '.uniqid();

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // verify no new entity was created
        $finalCount = \count($this->repository->findAll());
        $this->assertEquals($initialCount, $finalCount, 'Edit should not create new entities');
    }

    public function testBooleanFieldCanBeToggled(): void
    {
        // get an entity with active = false, creating one if needed
        $entity = $this->ensureInactiveEntityExists();
        $entityId = $entity->getId();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entityId));
        $this->assertResponseIsSuccessful();

        // get the form and toggle the active field to true
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        // check the checkbox (set to true) - EasyAdmin uses '1' for true
        $form[$formName.'[active]'] = '1';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify the toggle
        $this->entityManager->clear();
        $updatedEntity = $this->repository->find($entityId);

        $this->assertTrue($updatedEntity->isActive(), 'Boolean field should be toggled to true');
    }

    public function testNullableFieldCanBeClearedOnEdit(): void
    {
        // create entity with non-null values - track for cleanup
        $entity = new DefaultCrudTestEntity();
        $entity->setName('Entity With Values');
        $entity->setDescription('Has description');
        $entity->setActive(true);
        $entity->setPriority(50);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $entityId = $entity->getId();
        $this->createdEntityIds[] = $entityId;

        $this->entityManager->clear();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entityId));
        $this->assertResponseIsSuccessful();

        // get the form and clear nullable fields
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[description]'] = ''; // Clear description

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify
        $this->entityManager->clear();
        $updatedEntity = $this->repository->find($entityId);

        $this->assertNull($updatedEntity->getDescription(), 'Nullable field should be cleared');
    }
}
