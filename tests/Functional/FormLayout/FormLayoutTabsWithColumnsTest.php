<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutTabsWithColumnsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for tabs with multi-column layouts.
 */
class FormLayoutTabsWithColumnsTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutTabsWithColumnsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testTabsAndColumnsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify tabs are present
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs navigation should be present');
        static::assertCount(3, $crawler->filter('.nav-tabs .nav-item'), 'Should have 3 tabs');

        // verify columns are present
        static::assertGreaterThan(0, $crawler->filter('.form-column')->count(), 'Columns should be present');
    }

    public function testFirstTabHasTwoColumns(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $firstTabPane = $crawler->filter('.tab-pane')->first();
        $columns = $firstTabPane->filter('.form-column');

        static::assertCount(2, $columns, 'First tab should have 2 columns');
    }

    public function testSecondTabHasThreeColumns(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondTabPane = $crawler->filter('.tab-pane')->eq(1);
        $columns = $secondTabPane->filter('.form-column');

        static::assertCount(3, $columns, 'Second tab should have 3 columns');
    }

    public function testThirdTabHasNoExplicitColumns(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $thirdTabPane = $crawler->filter('.tab-pane')->eq(2);

        // third tab has fields but no explicit column layout
        static::assertCount(1, $thirdTabPane->filter('[name="FormTestEntity[isActive]"]'), 'Third tab should have isActive field');
        static::assertCount(1, $thirdTabPane->filter('[name="FormTestEntity[priority]"]'), 'Third tab should have priority field');
    }

    public function testColumnWidthsAreCorrect(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // tab 1 columns should be col-6 (50% width each)
        $firstTabPane = $crawler->filter('.tab-pane')->first();
        $firstColumns = $firstTabPane->filter('.form-column');

        foreach ($firstColumns as $column) {
            $class = $column->getAttribute('class');
            // check for Bootstrap column class (col-6 or col-md-6 or similar)
            static::assertTrue(
                str_contains($class, 'col-6') || str_contains($class, 'col-md-6'),
                'First tab columns should have width 6'
            );
        }
    }

    public function testFieldsStayInTheirColumns(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $firstTabPane = $crawler->filter('.tab-pane')->first();
        $columns = $firstTabPane->filter('.form-column');

        // column 1 should have name and email
        $firstColumn = $columns->eq(0);
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[name]"]'), 'First column should have name field');
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[email]"]'), 'First column should have email field');

        // column 2 should have description and phone
        $secondColumn = $columns->eq(1);
        static::assertCount(1, $secondColumn->filter('[name="FormTestEntity[description]"]'), 'Second column should have description field');
        static::assertCount(1, $secondColumn->filter('[name="FormTestEntity[phone]"]'), 'Second column should have phone field');
    }

    public function testThreeColumnLayoutFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondTabPane = $crawler->filter('.tab-pane')->eq(1);
        $columns = $secondTabPane->filter('.form-column');

        // column 1 should have street
        $firstColumn = $columns->eq(0);
        static::assertCount(1, $firstColumn->filter('[name="FormTestEntity[street]"]'), 'First column should have street field');

        // column 2 should have city
        $secondColumn = $columns->eq(1);
        static::assertCount(1, $secondColumn->filter('[name="FormTestEntity[city]"]'), 'Second column should have city field');

        // column 3 should have postalCode and country
        $thirdColumn = $columns->eq(2);
        static::assertCount(1, $thirdColumn->filter('[name="FormTestEntity[postalCode]"]'), 'Third column should have postalCode field');
        static::assertCount(1, $thirdColumn->filter('[name="FormTestEntity[country]"]'), 'Third column should have country field');
    }

    public function testFormSubmissionPreservesDataAcrossTabsAndColumns(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill fields across different tabs and columns
        $form['FormTestEntity[name]'] = 'Tab Column Test';
        $form['FormTestEntity[email]'] = 'tabcolumn@test.com';
        $form['FormTestEntity[description]'] = 'Test description';
        $form['FormTestEntity[street]'] = '123 Column St';
        $form['FormTestEntity[city]'] = 'Column City';
        $form['FormTestEntity[priority]'] = '7';

        $this->client->submit($form);

        // verify entity was created with data from all tabs/columns
        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Tab Column Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('tabcolumn@test.com', $entity->getEmail());
        static::assertSame('Test description', $entity->getDescription());
        static::assertSame('123 Column St', $entity->getStreet());
        static::assertSame('Column City', $entity->getCity());
        static::assertSame(7, $entity->getPriority());
    }

    public function testSwitchingTabsPreservesData(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // this test verifies the form structure supports tab switching
        // in a real scenario with JavaScript, data would be preserved when switching tabs
        // here we verify the structure is correct for that behavior

        $form = $crawler->filter('form.ea-new-form')->form();
        $form['FormTestEntity[name]'] = 'Switch Test';
        $form['FormTestEntity[street]'] = '456 Switch Ave';

        $this->client->submit($form);

        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Switch Test']);

        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('456 Switch Ave', $entity->getStreet(), 'Data from different tabs should be preserved');
    }

    public function testEditFormPreservesTabsAndColumns(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Tabs Columns');
        $entity->setEmail('edit@tabcolumn.com');
        $entity->setStreet('789 Edit Blvd');
        $entity->setCity('Edit Town');
        $entity->setPriority(4);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // verify tabs and columns are present
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs should be present in edit form');
        static::assertGreaterThan(0, $crawler->filter('.form-column')->count(), 'Columns should be present in edit form');

        // verify data is loaded
        $form = $crawler->filter('form.ea-edit-form')->form();
        static::assertSame('Edit Tabs Columns', $form['FormTestEntity[name]']->getValue());
        static::assertSame('edit@tabcolumn.com', $form['FormTestEntity[email]']->getValue());
        static::assertSame('789 Edit Blvd', $form['FormTestEntity[street]']->getValue());
    }

    public function testTabLabels(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $tabsNav = $crawler->filter('.nav-tabs');
        static::assertStringContainsString('Basic Information', $tabsNav->text(), 'Should have Basic Information tab');
        static::assertStringContainsString('Address', $tabsNav->text(), 'Should have Address tab');
        static::assertStringContainsString('Settings', $tabsNav->text(), 'Should have Settings tab');
    }

    public function testTabIcons(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $tabItems = $crawler->filter('.nav-tabs .nav-item');
        static::assertStringContainsString('fa-user', $tabItems->eq(0)->html(), 'First tab should have user icon');
        static::assertStringContainsString('fa-map-marker', $tabItems->eq(1)->html(), 'Second tab should have map marker icon');
        static::assertStringContainsString('fa-cog', $tabItems->eq(2)->html(), 'Third tab should have cog icon');
    }

    public function testFirstTabIsActiveByDefault(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $firstTabLink = $crawler->filter('.nav-tabs .nav-item:first-child .nav-link');
        static::assertStringContainsString('active', $firstTabLink->attr('class'), 'First tab should be active by default');

        $firstPane = $crawler->filter('.tab-pane')->first();
        static::assertStringContainsString('active', $firstPane->attr('class'), 'First tab pane should be active');
    }
}
