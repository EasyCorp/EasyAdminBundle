<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutValidationErrorsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for complex form layouts with required fields.
 * Note: These tests verify the form structure and field configuration for layouts
 * that would support validation error display. Full server-side validation testing
 * would require entity-level validation constraints.
 */
class FormLayoutValidationErrorsTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutValidationErrorsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFormLayoutWithRequiredFieldsRendersCorrectly(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify tabs are present
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs should be present');
        static::assertCount(3, $crawler->filter('.nav-tabs .nav-item'), 'Should have 3 tabs');

        // verify columns are present in tab 2
        $tabPanes = $crawler->filter('.tab-pane');
        static::assertGreaterThan(0, $tabPanes->eq(1)->filter('.form-column')->count(), 'Second tab should have columns');

        // verify fieldsets are present in tab 3
        static::assertGreaterThan(0, $tabPanes->eq(2)->filter('.form-fieldset')->count(), 'Third tab should have fieldsets');
    }

    public function testRequiredFieldsAreInCorrectTabs(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $tabPanes = $crawler->filter('.tab-pane');

        // tab 1: name, email (required)
        $firstTab = $tabPanes->eq(0);
        static::assertCount(1, $firstTab->filter('[name="FormTestEntity[name]"]'), 'First tab should have name field');
        static::assertCount(1, $firstTab->filter('[name="FormTestEntity[email]"]'), 'First tab should have email field');

        // tab 2: street, city, country (required)
        $secondTab = $tabPanes->eq(1);
        static::assertCount(1, $secondTab->filter('[name="FormTestEntity[street]"]'), 'Second tab should have street field');
        static::assertCount(1, $secondTab->filter('[name="FormTestEntity[city]"]'), 'Second tab should have city field');
        static::assertCount(1, $secondTab->filter('[name="FormTestEntity[country]"]'), 'Second tab should have country field');

        // tab 3: priority (required, in collapsed fieldset)
        $thirdTab = $tabPanes->eq(2);
        static::assertCount(1, $thirdTab->filter('[name="FormTestEntity[priority]"]'), 'Third tab should have priority field');
    }

    public function testRequiredFieldsInColumnsLayout(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondTabPane = $crawler->filter('.tab-pane')->eq(1);
        $columns = $secondTabPane->filter('.form-column');

        static::assertCount(2, $columns, 'Address tab should have 2 columns');

        // column 1 should have street and city
        $firstColumn = $columns->eq(0);
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[street]"]'), 'First column should have street');
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[city]"]'), 'First column should have city');

        // column 2 should have country
        $secondColumn = $columns->eq(1);
        static::assertCount(1, $secondColumn->filter('[name="FormTestEntity[country]"]'), 'Second column should have country');
    }

    public function testRequiredFieldInCollapsedFieldset(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $thirdTabPane = $crawler->filter('.tab-pane')->eq(2);

        // verify Status Settings fieldset exists
        static::assertStringContainsString('Status Settings', $thirdTabPane->html(), 'Status Settings fieldset should be present');

        // verify priority field exists in the fieldset
        static::assertCount(1, $thirdTabPane->filter('[name="FormTestEntity[priority]"]'), 'Priority field should exist in collapsed fieldset');
    }

    public function testFormSubmissionWithAllRequiredFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill all required fields
        $form['FormTestEntity[name]'] = 'Complete Validation Test';
        $form['FormTestEntity[email]'] = 'validation@test.com';
        $form['FormTestEntity[street]'] = '123 Test St';
        $form['FormTestEntity[city]'] = 'Test City';
        $form['FormTestEntity[country]'] = 'US';
        $form['FormTestEntity[priority]'] = '5';

        $this->client->submit($form);

        // verify entity was created
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Complete Validation Test']);

        static::assertNotNull($entity, 'Entity should be created with all required fields');
        static::assertSame('validation@test.com', $entity->getEmail());
        static::assertSame('123 Test St', $entity->getStreet());
        static::assertSame('Test City', $entity->getCity());
        static::assertSame('US', $entity->getCountry());
        static::assertSame(5, $entity->getPriority());
    }

    public function testFormSubmissionWithDataFromAllTabs(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill fields from all three tabs
        $form['FormTestEntity[name]'] = 'Multi Tab Test';
        $form['FormTestEntity[email]'] = 'multitab@test.com';
        $form['FormTestEntity[description]'] = 'Tab 1 field';
        $form['FormTestEntity[street]'] = '456 Tab2 Ave';
        $form['FormTestEntity[city]'] = 'Tab2 City';
        $form['FormTestEntity[country]'] = 'CA';
        $form['FormTestEntity[postalCode]'] = '12345';
        $form['FormTestEntity[priority]'] = '8';
        $form['FormTestEntity[status]'] = 'published';

        $this->client->submit($form);

        // verify all data from all tabs was saved
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Multi Tab Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('multitab@test.com', $entity->getEmail());
        static::assertSame('Tab 1 field', $entity->getDescription());
        static::assertSame('456 Tab2 Ave', $entity->getStreet());
        static::assertSame('Tab2 City', $entity->getCity());
        static::assertSame('CA', $entity->getCountry());
        static::assertSame('12345', $entity->getPostalCode());
        static::assertSame(8, $entity->getPriority());
        static::assertSame('published', $entity->getStatus());
    }

    public function testFormSubmissionWithDataFromCollapsedFieldset(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill required fields plus data in collapsed fieldset
        // priority is in the collapsed "Status Settings" fieldset
        $form['FormTestEntity[name]'] = 'Collapsed Fieldset Test';
        $form['FormTestEntity[email]'] = 'collapsed@test.com';
        $form['FormTestEntity[street]'] = '789 Collapse Rd';
        $form['FormTestEntity[city]'] = 'Collapse Town';
        $form['FormTestEntity[country]'] = 'GB';
        $form['FormTestEntity[priority]'] = '3';

        $this->client->submit($form);

        // verify data from collapsed fieldset was saved
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Collapsed Fieldset Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame(3, $entity->getPriority(), 'Priority from collapsed fieldset should be saved');
    }

    public function testFieldsetErrorBadgeOnValidationErrors(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill all required fields EXCEPT name (NotBlank) and priority (NotNull, inside collapsed fieldset)
        $form['FormTestEntity[email]'] = 'test@test.com';
        $form['FormTestEntity[street]'] = '123 Street';
        $form['FormTestEntity[city]'] = 'City';
        $form['FormTestEntity[country]'] = 'US';

        $this->client->followRedirects(false);
        $crawler = $this->client->submit($form);

        // form should be re-displayed with validation errors (422 in Symfony 6.2+)
        $statusCode = $this->client->getResponse()->getStatusCode();
        static::assertTrue(422 === $statusCode || 200 === $statusCode, 'Form should be re-displayed with validation errors');

        // the collapsed "Status Settings" fieldset should have the error badge
        $statusFieldset = $crawler->filter('.form-fieldset:contains("Status Settings")');
        static::assertGreaterThan(0, $statusFieldset->count(), 'Status Settings fieldset should be present');
        static::assertCount(1, $statusFieldset->filter('.badge-danger'), 'Collapsed fieldset with errors should show an error badge');
    }

    public function testCollapsedFieldsetAutoExpandsOnValidationErrors(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // submit without the priority field (NotNull, inside collapsed fieldset)
        $form['FormTestEntity[name]'] = 'Test';
        $form['FormTestEntity[email]'] = 'test@test.com';
        $form['FormTestEntity[street]'] = '123 Street';
        $form['FormTestEntity[city]'] = 'City';
        $form['FormTestEntity[country]'] = 'US';

        $this->client->followRedirects(false);
        $crawler = $this->client->submit($form);

        $statusCode = $this->client->getResponse()->getStatusCode();
        static::assertTrue(422 === $statusCode || 200 === $statusCode, 'Form should be re-displayed with validation errors');

        // the collapsed "Status Settings" fieldset should be auto-expanded (body has 'show' class)
        $statusFieldset = $crawler->filter('.form-fieldset:contains("Status Settings")');
        static::assertCount(1, $statusFieldset->filter('.form-fieldset-body.show'), 'Collapsed fieldset with errors should be auto-expanded');

        // the collapse toggle should not have the 'collapsed' class
        static::assertCount(0, $statusFieldset->filter('.form-fieldset-collapse.collapsed'), 'Auto-expanded fieldset toggle should not have collapsed class');
    }

    public function testFieldsetWithoutErrorsHasNoBadge(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // submit without name to trigger a validation error on tab 1 only
        $form['FormTestEntity[email]'] = 'test@test.com';
        $form['FormTestEntity[street]'] = '123 Street';
        $form['FormTestEntity[city]'] = 'City';
        $form['FormTestEntity[country]'] = 'US';
        $form['FormTestEntity[priority]'] = '5';

        $this->client->followRedirects(false);
        $crawler = $this->client->submit($form);

        $statusCode = $this->client->getResponse()->getStatusCode();
        static::assertTrue(422 === $statusCode || 200 === $statusCode, 'Form should be re-displayed with validation errors');

        // the "Metadata" fieldset (no errors) should NOT have an error badge
        $metadataFieldset = $crawler->filter('.form-fieldset:contains("Metadata")');
        static::assertCount(0, $metadataFieldset->filter('.badge-danger'), 'Fieldset without errors should not show an error badge');
    }

    public function testFieldsetErrorBadgeShowsCorrectCount(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // submit without priority (1 error in "Status Settings" fieldset)
        $form['FormTestEntity[name]'] = 'Test';
        $form['FormTestEntity[email]'] = 'test@test.com';
        $form['FormTestEntity[street]'] = '123 Street';
        $form['FormTestEntity[city]'] = 'City';
        $form['FormTestEntity[country]'] = 'US';

        $this->client->followRedirects(false);
        $crawler = $this->client->submit($form);

        $statusCode = $this->client->getResponse()->getStatusCode();
        static::assertTrue(422 === $statusCode || 200 === $statusCode, 'Form should be re-displayed with validation errors');

        $statusFieldset = $crawler->filter('.form-fieldset:contains("Status Settings")');
        $badge = $statusFieldset->filter('.badge-danger');
        static::assertCount(1, $badge, 'Should have exactly one error badge');
        static::assertSame('1', trim($badge->text()), 'Error badge should show count of 1');
    }

    public function testFieldsetWithErrorsHasErrorClass(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // submit without priority to trigger error in "Status Settings" fieldset
        $form['FormTestEntity[name]'] = 'Test';
        $form['FormTestEntity[email]'] = 'test@test.com';
        $form['FormTestEntity[street]'] = '123 Street';
        $form['FormTestEntity[city]'] = 'City';
        $form['FormTestEntity[country]'] = 'US';

        $this->client->followRedirects(false);
        $crawler = $this->client->submit($form);

        $statusCode = $this->client->getResponse()->getStatusCode();
        static::assertTrue(422 === $statusCode || 200 === $statusCode, 'Form should be re-displayed with validation errors');

        // the fieldset with errors should have 'has-fieldset-error' class
        $statusFieldset = $crawler->filter('.form-fieldset:contains("Status Settings")');
        static::assertCount(1, $statusFieldset->filter('.has-fieldset-error'), 'Fieldset with errors should have has-fieldset-error class');
    }

    public function testEditFormWithComplexLayout(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Complex Layout');
        $entity->setEmail('edit@complex.com');
        $entity->setStreet('321 Edit St');
        $entity->setCity('Edit City');
        $entity->setCountry('FR');
        $entity->setPriority(7);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // verify layout is preserved in edit form
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs should be present in edit form');
        static::assertCount(3, $crawler->filter('.nav-tabs .nav-item'), 'Should have 3 tabs in edit form');

        // verify data is loaded
        $form = $crawler->filter('form.ea-edit-form')->form();
        static::assertSame('Edit Complex Layout', $form['FormTestEntity[name]']->getValue());
        static::assertSame('edit@complex.com', $form['FormTestEntity[email]']->getValue());
        static::assertSame('321 Edit St', $form['FormTestEntity[street]']->getValue());
    }
}
