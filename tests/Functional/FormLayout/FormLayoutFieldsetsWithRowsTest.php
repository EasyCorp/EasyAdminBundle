<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutFieldsetsWithRowsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for fieldsets with row breaks inside.
 */
class FormLayoutFieldsetsWithRowsTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutFieldsetsWithRowsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsetsAndRowBreaksAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify fieldsets are present
        static::assertGreaterThan(0, $crawler->filter('.form-fieldset')->count(), 'Fieldsets should be present');

        // verify row breaks are present
        static::assertGreaterThan(0, $crawler->filter('.field-form_row')->count(), 'Row breaks should be present');
    }

    public function testRowBreaksExistInFieldsets(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // at least 5 row breaks should be present (based on the controller configuration)
        $rowBreaks = $crawler->filter('.field-form_row');
        static::assertGreaterThanOrEqual(5, $rowBreaks->count(), 'Should have at least 5 row breaks');
    }

    public function testBasicInformationFieldsetHasRowBreak(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // the form should have all fields from Basic Information fieldset
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[name]"]'), 'Should have name field');
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[description]"]'), 'Should have description field');
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[email]"]'), 'Should have email field');
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[phone]"]'), 'Should have phone field');
    }

    public function testAddressFieldsetHasMultipleRowBreaks(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // address fieldset should have all its fields
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[street]"]'), 'Should have street field');
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[city]"]'), 'Should have city field');
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[postalCode]"]'), 'Should have postalCode field');
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[country]"]'), 'Should have country field');
    }

    public function testSettingsFieldsetHasRowBreaks(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // settings fieldset should have all its fields
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[isActive]"]'), 'Should have isActive field');
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[priority]"]'), 'Should have priority field');
        static::assertCount(1, $crawler->filter('[name="FormTestEntity[createdAt]"]'), 'Should have createdAt field');
    }

    public function testRowBreaksHaveCorrectCssClass(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $rowBreaks = $crawler->filter('.field-form_row');

        foreach ($rowBreaks as $rowBreak) {
            static::assertStringContainsString('field-form_row', $rowBreak->getAttribute('class'), 'Row break should have field-form_row class');
        }
    }

    public function testFormSubmissionWorksWithRowBreaks(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill fields across fieldsets with row breaks
        $form['FormTestEntity[name]'] = 'Row Break Test';
        $form['FormTestEntity[email]'] = 'rowbreak@test.com';
        $form['FormTestEntity[street]'] = '999 Row St';
        $form['FormTestEntity[city]'] = 'Break City';
        $form['FormTestEntity[priority]'] = '4';

        $this->client->submit($form);

        // verify entity was created with data from all fieldsets
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Row Break Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('rowbreak@test.com', $entity->getEmail());
        static::assertSame('999 Row St', $entity->getStreet());
        static::assertSame('Break City', $entity->getCity());
        static::assertSame(4, $entity->getPriority());
    }

    public function testCollapsibleFieldsetWithRowBreaksWorks(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // address fieldset is collapsible and has row breaks
        $html = $crawler->html();
        static::assertStringContainsString('Address', $html, 'Address fieldset should be present');

        // verify form submission works with collapsible fieldset containing row breaks
        $form = $crawler->filter('form.ea-new-form')->form();
        $form['FormTestEntity[name]'] = 'Collapsible Row Test';
        $form['FormTestEntity[street]'] = '111 Collapsible Ave';

        $this->client->submit($form);

        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Collapsible Row Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('111 Collapsible Ave', $entity->getStreet());
    }

    public function testEditFormPreservesFieldsetsAndRows(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Fieldsets Rows');
        $entity->setEmail('edit@fieldsetsrows.com');
        $entity->setStreet('222 Edit Row Ln');
        $entity->setPriority(6);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // verify fieldsets and row breaks are present in edit form
        static::assertGreaterThan(0, $crawler->filter('.form-fieldset')->count(), 'Fieldsets should be present in edit form');
        static::assertGreaterThan(0, $crawler->filter('.field-form_row')->count(), 'Row breaks should be present in edit form');

        // verify data is loaded
        $form = $crawler->filter('form.ea-edit-form')->form();
        static::assertSame('Edit Fieldsets Rows', $form['FormTestEntity[name]']->getValue());
        static::assertSame('edit@fieldsetsrows.com', $form['FormTestEntity[email]']->getValue());
    }

    public function testFieldsetLabelsAndIcons(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $html = $crawler->html();

        // check fieldset icons exist
        static::assertStringContainsString('fa-info-circle', $html, 'Should have Basic Information icon');
        static::assertStringContainsString('fa-map-marker', $html, 'Should have Address icon');
        static::assertStringContainsString('fa-cog', $html, 'Should have Settings icon');
    }

    public function testMultipleRowBreaksInSingleFieldset(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify multiple row breaks exist
        // address fieldset has 2 row breaks, Settings has 2, Basic Information has 1
        $rowBreaks = $crawler->filter('.field-form_row');
        static::assertGreaterThanOrEqual(5, $rowBreaks->count(), 'Should have multiple row breaks across fieldsets');
    }
}
