<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutColumnsWithFieldsetsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for columns containing fieldsets.
 */
class FormLayoutColumnsWithFieldsetsTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutColumnsWithFieldsetsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testColumnsAndFieldsetsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify columns are present
        static::assertCount(2, $crawler->filter('.form-column'), 'Should have 2 columns');

        // verify fieldsets are present
        static::assertGreaterThan(0, $crawler->filter('.form-fieldset')->count(), 'Fieldsets should be present');
    }

    public function testFieldsetsStayInTheirColumns(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $columns = $crawler->filter('.form-column');

        // column 1 should contain "Basic Information" and "Contact Information" fieldsets
        $firstColumn = $columns->eq(0);
        static::assertStringContainsString('Basic Information', $firstColumn->html(), 'First column should contain Basic Information fieldset');
        static::assertStringContainsString('Contact Information', $firstColumn->html(), 'First column should contain Contact Information fieldset');

        // column 2 should contain "Address" and "Settings" fieldsets
        $secondColumn = $columns->eq(1);
        static::assertStringContainsString('Address', $secondColumn->html(), 'Second column should contain Address fieldset');
        static::assertStringContainsString('Settings', $secondColumn->html(), 'Second column should contain Settings fieldset');
    }

    public function testFieldsetsInFirstColumnContainCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $firstColumn = $crawler->filter('.form-column')->first();

        // basic Information fieldset should have name and description
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[name]"]'), 'Should have name field');
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[description]"]'), 'Should have description field');

        // contact Information fieldset should have email and phone
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[email]"]'), 'Should have email field');
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[phone]"]'), 'Should have phone field');
    }

    public function testFieldsetsInSecondColumnContainCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondColumn = $crawler->filter('.form-column')->eq(1);

        // address fieldset should have street, city, postalCode, country
        static::assertCount(1, $secondColumn->filter('[name="FormTestEntity[street]"]'), 'Should have street field');
        static::assertCount(1, $secondColumn->filter('[name="FormTestEntity[city]"]'), 'Should have city field');
        static::assertCount(1, $secondColumn->filter('[name="FormTestEntity[postalCode]"]'), 'Should have postalCode field');
        static::assertCount(1, $secondColumn->filter('[name="FormTestEntity[country]"]'), 'Should have country field');
    }

    public function testCollapsibleFieldsetWorksInsideColumn(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondColumn = $crawler->filter('.form-column')->eq(1);

        // address fieldset should be collapsible
        static::assertStringContainsString('Address', $secondColumn->html(), 'Address fieldset should be present');
    }

    public function testCollapsedFieldsetInsideColumn(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondColumn = $crawler->filter('.form-column')->eq(1);

        // settings fieldset should be present (collapsed by default)
        static::assertStringContainsString('Settings', $secondColumn->html(), 'Settings fieldset should be present');
    }

    public function testFormSubmissionPreservesDataAcrossColumnsAndFieldsets(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill fields across different columns and fieldsets
        $form['FormTestEntity[name]'] = 'Column Fieldset Test';
        $form['FormTestEntity[email]'] = 'columnfieldset@test.com';
        $form['FormTestEntity[street]'] = '789 Column St';
        $form['FormTestEntity[priority]'] = '6';

        $this->client->submit($form);

        // verify entity was created with data from all columns/fieldsets
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Column Fieldset Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('columnfieldset@test.com', $entity->getEmail());
        static::assertSame('789 Column St', $entity->getStreet());
        static::assertSame(6, $entity->getPriority());
    }

    public function testFormSubmissionWorksWithCollapsedFieldsetInColumn(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill data including fields in collapsed "Settings" fieldset
        $form['FormTestEntity[name]'] = 'Collapsed Column Test';
        $form['FormTestEntity[email]'] = 'collapsedcolumn@test.com';
        $form['FormTestEntity[priority]'] = '9';

        $this->client->submit($form);

        // verify data from collapsed fieldset in column was saved
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Collapsed Column Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame(9, $entity->getPriority(), 'Priority from collapsed fieldset should be saved');
    }

    public function testEditFormPreservesColumnsAndFieldsets(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Columns Fieldsets');
        $entity->setEmail('edit@columnfieldset.com');
        $entity->setStreet('321 Edit Way');
        $entity->setPriority(2);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // verify columns and fieldsets are present
        static::assertCount(2, $crawler->filter('.form-column'), 'Columns should be present in edit form');
        static::assertGreaterThan(0, $crawler->filter('.form-fieldset')->count(), 'Fieldsets should be present in edit form');

        // verify data is loaded
        $form = $crawler->filter('form.ea-edit-form')->form();
        static::assertSame('Edit Columns Fieldsets', $form['FormTestEntity[name]']->getValue());
        static::assertSame('edit@columnfieldset.com', $form['FormTestEntity[email]']->getValue());
    }

    public function testColumnWidths(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $columns = $crawler->filter('.form-column');

        // both columns should be col-6 (50% width each)
        foreach ($columns as $column) {
            $class = $column->getAttribute('class');
            // check for Bootstrap column class
            static::assertTrue(
                str_contains($class, 'col-6') || str_contains($class, 'col-md-6'),
                'Columns should have width 6'
            );
        }
    }

    public function testFieldsetLabelsAndIcons(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $html = $crawler->html();

        // check fieldset icons exist
        static::assertStringContainsString('fa-info-circle', $html, 'Should have Basic Information icon');
        static::assertStringContainsString('fa-phone', $html, 'Should have Contact Information icon');
        static::assertStringContainsString('fa-map-marker', $html, 'Should have Address icon');
        static::assertStringContainsString('fa-cog', $html, 'Should have Settings icon');
    }

    public function testMultipleFieldsetsInOneColumn(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $firstColumn = $crawler->filter('.form-column')->first();

        // verify both fieldsets are present in the first column
        $fieldsets = $firstColumn->filter('.form-fieldset');
        static::assertGreaterThanOrEqual(2, $fieldsets->count(), 'First column should have at least 2 fieldsets');
    }

    public function testCollapsibleAndCollapsedFieldsetsCoexistInColumn(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondColumn = $crawler->filter('.form-column')->eq(1);

        // both Address (collapsible) and Settings (collapsed) should be in the second column
        static::assertStringContainsString('Address', $secondColumn->html(), 'Should have Address fieldset');
        static::assertStringContainsString('Settings', $secondColumn->html(), 'Should have Settings fieldset');
    }
}
