<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutTabsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for form tab layout functionality.
 */
class TabsTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        // clear all existing entities to ensure test isolation
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete(FormTestEntity::class, 'e')->getQuery()->execute();
        $this->entityManager->clear();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutTabsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testTabsAreRenderedInNewForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // check that tabs navigation is present
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs navigation should be present');

        // check that all 4 tabs are rendered
        static::assertCount(4, $crawler->filter('.nav-tabs .nav-item'), 'Should have 4 tabs');

        // check tab labels
        static::assertStringContainsString('Basic Info', $crawler->filter('.nav-tabs')->text());
        static::assertStringContainsString('Contact', $crawler->filter('.nav-tabs')->text());
        static::assertStringContainsString('Address', $crawler->filter('.nav-tabs')->text());
        static::assertStringContainsString('Settings', $crawler->filter('.nav-tabs')->text());
    }

    public function testTabsHaveCorrectIcons(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $tabItems = $crawler->filter('.nav-tabs .nav-item');

        // check icons in each tab
        static::assertStringContainsString('fa-info-circle', $tabItems->eq(0)->html());
        static::assertStringContainsString('fa-phone', $tabItems->eq(1)->html());
        static::assertStringContainsString('fa-map-marker', $tabItems->eq(2)->html());
        static::assertStringContainsString('fa-cog', $tabItems->eq(3)->html());
    }

    public function testFirstTabIsActiveByDefault(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // the first tab should have the 'active' class
        $firstTabLink = $crawler->filter('.nav-tabs .nav-item:first-child .nav-link');
        static::assertStringContainsString('active', $firstTabLink->attr('class'));

        // the first tab pane should be visible
        $tabPanes = $crawler->filter('.tab-pane');
        static::assertTrue($tabPanes->count() > 0, 'Tab panes should exist');

        // first tab pane should have active class
        $firstPane = $tabPanes->first();
        static::assertStringContainsString('active', $firstPane->attr('class'));
    }

    public function testTabPanesContainCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $tabPanes = $crawler->filter('.tab-pane');

        // tab 1 (Basic Info): name, description
        $firstPane = $tabPanes->eq(0);
        static::assertCount(1, $firstPane->filter('[name="FormTestEntity[name]"]'), 'First tab should contain name field');
        static::assertCount(1, $firstPane->filter('[name="FormTestEntity[description]"]'), 'First tab should contain description field');

        // tab 2 (Contact): email, phone
        $secondPane = $tabPanes->eq(1);
        static::assertCount(1, $secondPane->filter('[name="FormTestEntity[email]"]'), 'Second tab should contain email field');
        static::assertCount(1, $secondPane->filter('[name="FormTestEntity[phone]"]'), 'Second tab should contain phone field');

        // tab 3 (Address): street, city, postalCode, country
        $thirdPane = $tabPanes->eq(2);
        static::assertCount(1, $thirdPane->filter('[name="FormTestEntity[street]"]'), 'Third tab should contain street field');
        static::assertCount(1, $thirdPane->filter('[name="FormTestEntity[city]"]'), 'Third tab should contain city field');
        static::assertCount(1, $thirdPane->filter('[name="FormTestEntity[postalCode]"]'), 'Third tab should contain postalCode field');
        static::assertCount(1, $thirdPane->filter('[name="FormTestEntity[country]"]'), 'Third tab should contain country field');

        // tab 4 (Settings): isActive, createdAt, tags, priority
        $fourthPane = $tabPanes->eq(3);
        static::assertCount(1, $fourthPane->filter('[name="FormTestEntity[isActive]"]'), 'Fourth tab should contain isActive field');
        static::assertCount(1, $fourthPane->filter('[name="FormTestEntity[createdAt]"]'), 'Fourth tab should contain createdAt field');
        static::assertCount(1, $fourthPane->filter('[name="FormTestEntity[priority]"]'), 'Fourth tab should contain priority field');
    }

    public function testTabNavigationHasCorrectDataAttributes(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // check that tab links have data-bs-toggle="tab" for Bootstrap tab behavior
        $tabLinks = $crawler->filter('.nav-tabs .nav-link');
        foreach ($tabLinks as $tabLink) {
            static::assertSame('tab', $tabLink->getAttribute('data-bs-toggle'));
        }
    }

    public function testFormSubmissionWorksWithTabs(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();
        $form['FormTestEntity[name]'] = 'Test Entity';
        $form['FormTestEntity[email]'] = 'test@example.com';

        $this->client->submit($form);

        // clear entity manager to ensure fresh data from database
        $this->entityManager->clear();

        // check that entity was created
        $entity = $this->entityManager->getRepository(FormTestEntity::class)->findOneBy(['name' => 'Test Entity']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('test@example.com', $entity->getEmail());
    }

    public function testTabsAreRenderedInEditForm(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Test');
        $entity->setEmail('edit@test.com');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // check that tabs are present
        static::assertCount(1, $crawler->filter('.nav-tabs'), 'Tabs navigation should be present in edit form');
        static::assertCount(4, $crawler->filter('.nav-tabs .nav-item'), 'Should have 4 tabs in edit form');
    }
}
