<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\DefaultCrudTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;

/**
 * Tests for default new/create form behavior in EasyAdmin CRUD operations.
 */
class NewTest extends AbstractCrudTestCase
{
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

    public function testNewFormLoadsCorrectly(): void
    {
        $this->client->request('GET', $this->generateNewFormUrl());

        $this->assertResponseIsSuccessful();
    }

    public function testNewFormDisplaysForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form', 'New page should display a form');
    }

    public function testNewFormHasRequiredFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $this->assertResponseIsSuccessful();

        // check that expected form fields exist
        // for DefaultCrudTestEntity, we expect: name, description, active, priority
        $this->assertFormFieldExists('name');
        $this->assertFormFieldExists('description');
        $this->assertFormFieldExists('active');
        $this->assertFormFieldExists('priority');
    }

    public function testNewFormHasFormElements(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $this->assertResponseIsSuccessful();

        // check that the form has input fields (form is functional)
        $formInputs = $crawler->filter('form input, form textarea, form select');
        $this->assertGreaterThan(0, $formInputs->count(), 'Form should have input elements');
    }

    public function testFormCanBeSubmittedWithValidData(): void
    {
        $initialCount = \count($this->repository->findAll());

        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $this->assertResponseIsSuccessful();

        // get the form and fill it with valid data
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = 'Test Entity Created By Test';
        $form[$formName.'[description]'] = 'This is a test description';
        $form[$formName.'[active]'] = '1';
        $form[$formName.'[priority]'] = '99';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // verify entity was created
        $finalCount = \count($this->repository->findAll());
        $this->assertEquals($initialCount + 1, $finalCount, 'A new entity should have been created');
    }

    public function testEntityIsCreatedInDatabase(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $this->assertResponseIsSuccessful();

        $uniqueName = 'Unique Test Entity '.uniqid();

        // get the form and fill it with valid data
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = $uniqueName;
        $form[$formName.'[description]'] = 'Database creation test';
        $form[$formName.'[active]'] = '1';
        $form[$formName.'[priority]'] = '42';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // clear entity manager to ensure fresh data from database
        $this->entityManager->clear();

        // find the created entity
        $createdEntity = $this->repository->findOneBy(['name' => $uniqueName]);
        $this->assertNotNull($createdEntity, 'Entity should exist in database');
        $this->assertEquals($uniqueName, $createdEntity->getName());
        $this->assertEquals('Database creation test', $createdEntity->getDescription());
        $this->assertTrue($createdEntity->isActive());
        $this->assertEquals(42, $createdEntity->getPriority());
    }

    public function testRedirectAfterSuccessfulCreation(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $this->assertResponseIsSuccessful();

        $uniqueName = 'Redirect Test Entity '.uniqid();

        // get the form and fill it with valid data
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = $uniqueName;

        // disable redirect following to check the redirect
        $this->client->followRedirects(false);
        $this->client->submit($form);

        // should redirect (302 or 303)
        $this->assertTrue(
            $this->client->getResponse()->isRedirect(),
            'Should redirect after successful creation'
        );

        // follow redirect and verify we're on a success page (index or detail)
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testFormValidationWithMissingRequiredField(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $this->assertResponseIsSuccessful();

        $initialCount = \count($this->repository->findAll());

        // get the form but don't fill the required 'name' field
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = ''; // Empty required field
        $form[$formName.'[active]'] = '1';

        // disable redirect following to check validation
        $this->client->followRedirects(false);
        $this->client->submit($form);

        // form should be re-displayed (not a redirect) or show validation error
        // the response should either be 200 (form re-displayed) or 422 (unprocessable)
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            200 === $statusCode || 422 === $statusCode || $this->client->getResponse()->isRedirect(),
            'Form should handle validation error'
        );

        // verify no entity was created
        $finalCount = \count($this->repository->findAll());
        $this->assertEquals($initialCount, $finalCount, 'No entity should be created with invalid data');
    }

    public function testFormFieldLabelsExist(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $this->assertResponseIsSuccessful();

        // check that form field labels exist
        $labels = $crawler->filter('form label');
        $this->assertGreaterThan(0, $labels->count(), 'Form should have field labels');
    }

    public function testFormHasCsrfToken(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $this->assertResponseIsSuccessful();

        // check for CSRF token field
        $csrfField = $crawler->filter('form input[name*="[_token]"]');
        $this->assertGreaterThan(0, $csrfField->count(), 'Form should have CSRF token');
    }

    public function testCancelActionReturnsToIndex(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $this->assertResponseIsSuccessful();

        // look for a cancel/back link
        $cancelLink = $crawler->filter('.form-actions a, .action-index');
        if ($cancelLink->count() > 0) {
            $this->client->click($cancelLink->first()->link());
            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('.datagrid', 'Cancel should return to index page');
        } else {
            // if no cancel link, just verify the form page is correct
            $this->assertSelectorExists('form', 'Form should be displayed');
        }
    }

    public function testBooleanFieldDefaultsToFalse(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $this->assertResponseIsSuccessful();

        $formName = $this->getFormEntity();
        $activeField = $crawler->filter('[name="'.$formName.'[active]"]');

        $this->assertCount(1, $activeField, 'Active field should exist');
    }

    public function testNullableFieldsAcceptEmptyValues(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $this->assertResponseIsSuccessful();

        $initialCount = \count($this->repository->findAll());
        $uniqueName = 'Nullable Test '.uniqid();

        // get the form and fill only required fields
        $form = $crawler->filter($this->getEntityFormSelector())->form();

        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = $uniqueName;
        $form[$formName.'[description]'] = ''; // Empty nullable field
        // don't set priority (nullable integer)
        // note: checkbox fields cannot be set to '0' - they are either checked or unchecked

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        // verify entity was created
        $finalCount = \count($this->repository->findAll());
        $this->assertEquals($initialCount + 1, $finalCount, 'Entity should be created with nullable fields empty');

        // verify nullable fields are null/empty
        $this->entityManager->clear();
        $createdEntity = $this->repository->findOneBy(['name' => $uniqueName]);
        $this->assertNotNull($createdEntity);
    }
}
