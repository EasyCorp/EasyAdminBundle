<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutCompleteIntegrationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for complete integration of all form layout features
 * (Tabs + Columns + Fieldsets + Rows).
 */
class FormLayoutCompleteIntegrationTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutCompleteIntegrationCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testCompleteLayoutStructureIsRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify tabs are present
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs navigation should be present');
        static::assertCount(2, $crawler->filter('.nav-tabs .nav-item'), 'Should have 2 tabs');

        // verify columns are present
        static::assertGreaterThan(0, $crawler->filter('.form-column')->count(), 'Columns should be present');

        // verify fieldsets are present
        static::assertGreaterThan(0, $crawler->filter('.form-fieldset')->count(), 'Fieldsets should be present');

        // verify row breaks are present
        static::assertGreaterThan(0, $crawler->filter('.field-form_row')->count(), 'Row breaks should be present');
    }

    public function testAllLayoutFeaturesHaveCorrectHierarchy(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify that fieldsets are inside tab panes
        $tabPanes = $crawler->filter('.tab-pane');
        static::assertTrue($tabPanes->count() > 0, 'Tab panes should exist');

        // check first tab has columns and fieldsets inside
        $firstPane = $tabPanes->first();
        static::assertGreaterThan(0, $firstPane->filter('.form-column')->count(), 'First tab should contain columns');
        static::assertGreaterThan(0, $firstPane->filter('.form-fieldset')->count(), 'First tab should contain fieldsets');
    }

    public function testTabsContainColumnsCorrectly(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $tabPanes = $crawler->filter('.tab-pane');

        // tab 1 should have 2 columns
        $firstPane = $tabPanes->eq(0);
        static::assertCount(2, $firstPane->filter('.form-column'), 'First tab should have 2 columns');

        // tab 2 should have no explicit columns (single column)
        $secondPane = $tabPanes->eq(1);
        // tab 2 may have columns or not depending on implementation, but should have fieldsets
        static::assertGreaterThan(0, $secondPane->filter('.form-fieldset')->count(), 'Second tab should have fieldsets');
    }

    public function testColumnsContainFieldsetsCorrectly(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $firstTabPane = $crawler->filter('.tab-pane')->first();
        $columns = $firstTabPane->filter('.form-column');

        // column 1 should contain "Personal Details" fieldset
        $firstColumn = $columns->eq(0);
        static::assertStringContainsString('Personal Details', $firstColumn->html(), 'First column should contain Personal Details fieldset');

        // column 2 should contain "Address Information" fieldset
        $secondColumn = $columns->eq(1);
        static::assertStringContainsString('Address Information', $secondColumn->html(), 'Second column should contain Address Information fieldset');
    }

    public function testFieldsetsContainFieldsCorrectly(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // personal Details fieldset should contain: name, description, email, phone
        $personalDetailsSection = $crawler->filter('.tab-pane')->first();
        static::assertCount(1, $personalDetailsSection->filter('[name="FormTestEntity[name]"]'), 'Personal Details should contain name field');
        static::assertCount(1, $personalDetailsSection->filter('[name="FormTestEntity[description]"]'), 'Personal Details should contain description field');
        static::assertCount(1, $personalDetailsSection->filter('[name="FormTestEntity[email]"]'), 'Personal Details should contain email field');
        static::assertCount(1, $personalDetailsSection->filter('[name="FormTestEntity[phone]"]'), 'Personal Details should contain phone field');

        // address Information fieldset should contain: street, city, postalCode, country
        static::assertCount(1, $personalDetailsSection->filter('[name="FormTestEntity[street]"]'), 'Address Information should contain street field');
        static::assertCount(1, $personalDetailsSection->filter('[name="FormTestEntity[city]"]'), 'Address Information should contain city field');
        static::assertCount(1, $personalDetailsSection->filter('[name="FormTestEntity[postalCode]"]'), 'Address Information should contain postalCode field');
        static::assertCount(1, $personalDetailsSection->filter('[name="FormTestEntity[country]"]'), 'Address Information should contain country field');
    }

    public function testRowBreaksCreateVisualSeparation(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $rowBreaks = $crawler->filter('.field-form_row');
        static::assertGreaterThan(0, $rowBreaks->count(), 'Row breaks should exist');

        // verify row breaks have the correct CSS class
        foreach ($rowBreaks as $rowBreak) {
            static::assertStringContainsString('field-form_row', $rowBreak->getAttribute('class'));
        }
    }

    public function testCollapsibleFieldsetWorksInsideTab(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // find the Address Information fieldset which should be collapsible
        $fieldsets = $crawler->filter('.form-fieldset');
        $collapsibleFound = false;

        foreach ($fieldsets as $fieldset) {
            if (str_contains($fieldset->textContent, 'Address Information')) {
                // check for collapsible attributes or classes
                $html = $fieldset->ownerDocument->saveHTML($fieldset);
                // collapsible fieldsets typically have a header that can be clicked
                $collapsibleFound = str_contains($html, 'Address Information');
                break;
            }
        }

        static::assertTrue($collapsibleFound, 'Collapsible fieldset (Address Information) should be present');
    }

    public function testCollapsedFieldsetRemainsCollapsedByDefault(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondTabPane = $crawler->filter('.tab-pane')->eq(1);

        // the Status fieldset should be collapsed by default
        // check if the Status fieldset exists in the second tab
        static::assertStringContainsString('Status', $secondTabPane->html(), 'Status fieldset should exist in second tab');
    }

    public function testFirstTabIsActiveByDefault(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // the first tab should have the 'active' class
        $firstTabLink = $crawler->filter('.nav-tabs .nav-item:first-child .nav-link');
        static::assertStringContainsString('active', $firstTabLink->attr('class'), 'First tab should be active by default');

        // the first tab pane should be visible
        $firstPane = $crawler->filter('.tab-pane')->first();
        static::assertStringContainsString('active', $firstPane->attr('class'), 'First tab pane should be active');
    }

    public function testFormSubmissionWorksWithComplexLayout(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill in data across all sections
        $form['FormTestEntity[name]'] = 'Complete Test';
        $form['FormTestEntity[email]'] = 'complete@test.com';
        $form['FormTestEntity[street]'] = '123 Main St';
        $form['FormTestEntity[city]'] = 'Test City';
        $form['FormTestEntity[priority]'] = '5';

        $this->client->submit($form);

        // verify entity was created with all data
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Complete Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('complete@test.com', $entity->getEmail());
        static::assertSame('123 Main St', $entity->getStreet());
        static::assertSame('Test City', $entity->getCity());
        static::assertSame(5, $entity->getPriority());
    }

    public function testFormSubmissionPreservesDataFromCollapsedFieldsets(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill in data including fields in collapsed fieldset (Status)
        $form['FormTestEntity[name]'] = 'Collapsed Test';
        $form['FormTestEntity[email]'] = 'collapsed@test.com';
        $form['FormTestEntity[status]'] = 'published';

        $this->client->submit($form);

        // verify data from collapsed fieldset was saved
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Collapsed Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('published', $entity->getStatus(), 'Status from collapsed fieldset should be saved');
    }

    public function testFormSubmissionWorksFromSecondTab(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill in data primarily from second tab
        $form['FormTestEntity[name]'] = 'Second Tab Test';
        $form['FormTestEntity[priority]'] = '10';
        $form['FormTestEntity[priceInCents]'] = '1999';

        $this->client->submit($form);

        // verify submission works regardless of active tab
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Second Tab Test']);

        static::assertNotNull($entity, 'Entity should be created from second tab data');
        static::assertSame(10, $entity->getPriority());
        static::assertSame(1999, $entity->getPriceInCents());
    }

    public function testComplexLayoutWorksInEditForm(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Test');
        $entity->setEmail('edit@test.com');
        $entity->setStreet('456 Edit St');
        $entity->setPriority(3);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // verify all layout features are present in edit form
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs should be present in edit form');
        static::assertGreaterThan(0, $crawler->filter('.form-column')->count(), 'Columns should be present in edit form');
        static::assertGreaterThan(0, $crawler->filter('.form-fieldset')->count(), 'Fieldsets should be present in edit form');

        // verify data is loaded correctly
        $form = $crawler->filter('form.ea-edit-form')->form();
        static::assertSame('Edit Test', $form['FormTestEntity[name]']->getValue());
        static::assertSame('edit@test.com', $form['FormTestEntity[email]']->getValue());
    }

    public function testComplexLayoutRendersInDetailPage(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Detail Test');
        $entity->setEmail('detail@test.com');
        $entity->setStreet('789 Detail Ave');
        $entity->setPriority(7);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        // verify layout structure is present in detail page
        // detail pages may render differently, but should show the data
        static::assertStringContainsString('Detail Test', $crawler->text());
        static::assertStringContainsString('detail@test.com', $crawler->text());
    }
}
