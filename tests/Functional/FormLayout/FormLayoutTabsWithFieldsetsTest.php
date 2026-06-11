<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutTabsWithFieldsetsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for tabs containing fieldsets.
 */
class FormLayoutTabsWithFieldsetsTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutTabsWithFieldsetsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testTabsAndFieldsetsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify tabs are present
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs navigation should be present');
        static::assertCount(2, $crawler->filter('.nav-tabs .nav-item'), 'Should have 2 tabs');

        // verify fieldsets are present
        static::assertGreaterThan(0, $crawler->filter('.form-fieldset')->count(), 'Fieldsets should be present');
    }

    public function testFieldsetsStayInTheirTab(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $tabPanes = $crawler->filter('.tab-pane');

        // tab 1 should contain "Basic Info" and "Contact" fieldsets
        $firstPane = $tabPanes->eq(0);
        static::assertStringContainsString('Basic Info', $firstPane->html(), 'First tab should contain Basic Info fieldset');
        static::assertStringContainsString('Contact', $firstPane->html(), 'First tab should contain Contact fieldset');

        // tab 2 should contain "Address" and "Advanced Settings" fieldsets
        $secondPane = $tabPanes->eq(1);
        static::assertStringContainsString('Address', $secondPane->html(), 'Second tab should contain Address fieldset');
        static::assertStringContainsString('Advanced Settings', $secondPane->html(), 'Second tab should contain Advanced Settings fieldset');
    }

    public function testFieldsetsContainCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $firstTabPane = $crawler->filter('.tab-pane')->first();

        // basic Info fieldset should have name and description
        static::assertCount(1, $firstTabPane->filter('[name="FormTestEntity[name]"]'), 'Basic Info should contain name field');
        static::assertCount(1, $firstTabPane->filter('[name="FormTestEntity[description]"]'), 'Basic Info should contain description field');

        // contact fieldset should have email and phone
        static::assertCount(1, $firstTabPane->filter('[name="FormTestEntity[email]"]'), 'Contact should contain email field');
        static::assertCount(1, $firstTabPane->filter('[name="FormTestEntity[phone]"]'), 'Contact should contain phone field');
    }

    public function testCollapsibleFieldsetWorksInsideTab(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondTabPane = $crawler->filter('.tab-pane')->eq(1);

        // address fieldset should be collapsible
        static::assertStringContainsString('Address', $secondTabPane->html(), 'Address fieldset should be present');
    }

    public function testCollapsedFieldsetInsideTab(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondTabPane = $crawler->filter('.tab-pane')->eq(1);

        // advanced Settings fieldset should be present (collapsed by default)
        static::assertStringContainsString('Advanced Settings', $secondTabPane->html(), 'Advanced Settings fieldset should be present');
    }

    public function testFormSubmissionPreservesDataAcrossTabsAndFieldsets(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill fields across different tabs and fieldsets
        $form['FormTestEntity[name]'] = 'Tab Fieldset Test';
        $form['FormTestEntity[email]'] = 'tabfieldset@test.com';
        $form['FormTestEntity[street]'] = '456 Test Ave';
        $form['FormTestEntity[priority]'] = '8';

        $this->client->submit($form);

        // verify entity was created with data from all tabs/fieldsets
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Tab Fieldset Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('tabfieldset@test.com', $entity->getEmail());
        static::assertSame('456 Test Ave', $entity->getStreet());
        static::assertSame(8, $entity->getPriority());
    }

    public function testFormSubmissionWorksWithCollapsedFieldset(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill data including fields in collapsed "Advanced Settings" fieldset
        $form['FormTestEntity[name]'] = 'Collapsed Fieldset Test';
        $form['FormTestEntity[email]'] = 'collapsed@test.com';
        $form['FormTestEntity[priority]'] = '3';

        $this->client->submit($form);

        // verify data from collapsed fieldset was saved
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Collapsed Fieldset Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame(3, $entity->getPriority(), 'Priority from collapsed fieldset should be saved');
    }

    public function testEditFormPreservesTabsAndFieldsets(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Tabs Fieldsets');
        $entity->setEmail('edit@tabfieldset.com');
        $entity->setStreet('789 Edit Rd');
        $entity->setPriority(5);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // verify tabs and fieldsets are present
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs should be present in edit form');
        static::assertGreaterThan(0, $crawler->filter('.form-fieldset')->count(), 'Fieldsets should be present in edit form');

        // verify data is loaded
        $form = $crawler->filter('form.ea-edit-form')->form();
        static::assertSame('Edit Tabs Fieldsets', $form['FormTestEntity[name]']->getValue());
        static::assertSame('edit@tabfieldset.com', $form['FormTestEntity[email]']->getValue());
    }

    public function testTabLabelsAndIcons(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // check tab labels
        $tabsNav = $crawler->filter('.nav-tabs');
        static::assertStringContainsString('User Details', $tabsNav->text(), 'Should have User Details tab');
        static::assertStringContainsString('Address & Settings', $tabsNav->text(), 'Should have Address & Settings tab');

        // check tab icons
        $tabItems = $crawler->filter('.nav-tabs .nav-item');
        static::assertStringContainsString('fa-user', $tabItems->eq(0)->html(), 'First tab should have user icon');
        static::assertStringContainsString('fa-cog', $tabItems->eq(1)->html(), 'Second tab should have cog icon');
    }

    public function testFieldsetLabelsAndIcons(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $html = $crawler->html();

        // check fieldset icons exist
        static::assertStringContainsString('fa-info-circle', $html, 'Should have Basic Info icon');
        static::assertStringContainsString('fa-phone', $html, 'Should have Contact icon');
        static::assertStringContainsString('fa-map-marker', $html, 'Should have Address icon');
        static::assertStringContainsString('fa-wrench', $html, 'Should have Advanced Settings icon');
    }
}
