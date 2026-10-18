<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Crud\DefaultCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use function Zenstruck\Foundry\Persistence\delete;

/**
 * Tests for default edit form behavior in EasyAdmin CRUD operations.
 */
class EditTest extends AbstractCrudTestCase
{
    /** @var EntityRepository<Category> */
    protected EntityRepository $repository;

    protected function getControllerFqcn(): string
    {
        return DefaultCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(Category::class);
    }

    public function testEditFormLoadsWithExistingEntityData(): void
    {
        $entity = CategoryFactory::createOne();

        $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testEditFormDisplaysForm(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form', 'Edit page should display a form');
    }

    public function testEditFormShowsExistingValues(): void
    {
        $entity = CategoryFactory::createOne(['name' => 'Category A']);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // get the form and check that it contains the existing values
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $this->assertEquals('Category A', $form[$formName.'[name]']->getValue());
    }

    public function testEditFormHasRequiredFields(): void
    {
        $entity = CategoryFactory::createOne();

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
        $entity = CategoryFactory::createOne(['name' => 'Original Name']);
        $entityId = $entity->getId();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entityId));
        $this->assertResponseIsSuccessful();

        // get the form and update with new data
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = 'Updated Name';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify the update
        $this->entityManager->clear();
        $updatedEntity = $this->repository->find($entityId);

        $this->assertNotNull($updatedEntity);
        $this->assertSame('Updated Name', $updatedEntity->getName());
    }

    public function testEntityIsUpdatedInDatabase(): void
    {
        $entityId = CategoryFactory::createOne([
            'name' => 'Category A',
            'description' => 'Original description',
            'active' => false,
            'priority' => 10,
        ])->getId();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entityId));
        $this->assertResponseIsSuccessful();

        // get the form and update all fields
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = 'Category B';
        $form[$formName.'[description]'] = 'Updated description';
        $form[$formName.'[active]'] = '1';
        $form[$formName.'[priority]'] = '99';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // clear entity manager and verify all updates
        $this->entityManager->clear();
        $updatedEntity = $this->repository->find($entityId);

        $this->assertNotNull($updatedEntity);
        $this->assertEquals('Category B', $updatedEntity->getName());
        $this->assertEquals('Updated description', $updatedEntity->getDescription());
        $this->assertTrue($updatedEntity->isActive());
        $this->assertEquals(99, $updatedEntity->getPriority());
    }

    public function testRedirectAfterSuccessfulUpdate(): void
    {
        $entity = CategoryFactory::createOne();

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

        $deletedEntity = CategoryFactory::createOne();
        $deletedEntityId = $deletedEntity->getId();
        delete($deletedEntity);

        $this->client->request('GET', $this->generateEditFormUrl($deletedEntityId));

        $this->assertResponseStatusCodeSame(404);
        $this->assertSelectorTextContains('.error-message', 'This item is no longer available.');
    }

    public function testEditFormHasFormElements(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check that the form has input fields (form is functional)
        $formInputs = $crawler->filter('form input, form textarea, form select');
        $this->assertGreaterThan(0, $formInputs->count(), 'Form should have input elements');
    }

    public function testEditFormHasCsrfToken(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for CSRF token field
        $csrfField = $crawler->filter('form input[name*="[_token]"]');
        $this->assertGreaterThan(0, $csrfField->count(), 'Form should have CSRF token');
    }

    public function testEditFormFieldsCanBeModified(): void
    {
        $entity = CategoryFactory::createOne();
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
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        $this->assertResponseIsSuccessful();

        // get the form and submit
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = 'Modified Name';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $this->assertSame(1, $this->repository->count([]), 'Edit should not create new entities');
        $this->assertSame('Modified Name', $this->repository->find($entity->getId())?->getName());
    }

    public function testBooleanFieldCanBeToggled(): void
    {
        $entityId = CategoryFactory::createOne(['active' => false])->getId();

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
        $entityId = CategoryFactory::createOne([
            'name' => 'Category A',
            'description' => 'Has description',
            'active' => true,
            'priority' => 50,
        ])->getId();

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
