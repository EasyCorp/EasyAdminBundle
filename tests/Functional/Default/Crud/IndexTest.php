<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\NoEntityActionsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\SomeRowsWithEntityActionsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Crud\DefaultCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

/**
 * Tests for default index page behavior in EasyAdmin CRUD operations.
 */
class IndexTest extends AbstractCrudTestCase
{
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
    }

    public function testIndexPageLoadsSuccessfully(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
    }

    public function testIndexPageShowsDatagrid(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.datagrid', 'The index page should display a datagrid');
        $this->assertSelectorExists('table.datagrid', 'The datagrid should be a table');
    }

    public function testDefaultPaginationIsPresent(): void
    {
        CategoryFactory::createMany(3);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check for pagination counter
        $this->assertSelectorExists('.list-pagination-counter', 'Pagination counter should be present');

        // check that entity count is displayed
        $this->assertIndexFullEntityCount(3);
    }

    public function testDefaultColumnsAreDisplayed(): void
    {
        CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that table headers exist
        $this->assertSelectorExists('thead', 'Table should have a header');
        $this->assertSelectorExists('thead tr', 'Table header should have a row');

        // check for common default columns
        $headers = $crawler->filter('thead th');
        $this->assertGreaterThan(0, $headers->count(), 'Table should have column headers');

        // by default EasyAdmin shows entity properties as columns
        // check that at least the name column exists
        $headerTexts = $crawler->filter('thead th')->extract(['_text']);
        $this->assertNotEmpty($headerTexts, 'Table should have column headers');
    }

    public function testDefaultGlobalActionsAreAvailable(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that the "New" action exists (global action)
        $this->assertGlobalActionExists(Action::NEW);
    }

    public function testDefaultEntityActionsAreAvailable(): void
    {
        $entity = CategoryFactory::createOne();

        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that Edit action exists for the entity
        $this->assertIndexEntityActionExists(Action::EDIT, $entity->getId());

        // check that Delete action exists for the entity
        $this->assertIndexEntityActionExists(Action::DELETE, $entity->getId());
    }

    public function testEntityCountIsDisplayedCorrectly(): void
    {
        CategoryFactory::createMany(2);

        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        $this->assertIndexFullEntityCount(2);
    }

    public function testIndexShowsEntitiesInTable(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that table body exists with rows
        $this->assertSelectorExists('tbody', 'Table should have a body');

        $rows = $crawler->filter('tbody tr[data-id]');
        $this->assertCount(2, $rows, 'Table should have one row per entity');
    }

    public function testEntityRowsExist(): void
    {
        $entities = CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that entity rows exist in the table body
        foreach ($entities as $entity) {
            $this->assertCount(1, $crawler->filter($this->getIndexEntityRowSelector($entity->getId())), 'Table should have a row for each entity');
        }
    }

    public function testActionsColumnExists(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check for actions column (contains edit/delete links)
        $actionsColumn = $crawler->filter('td.actions');
        $this->assertCount(2, $actionsColumn, 'Actions column should be present for each row');
    }

    public function testActionsColumnIsHiddenWhenNoRowHasActions(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, NoEntityActionsCrudController::class));

        $this->assertResponseIsSuccessful();

        $rows = $crawler->filter('tbody tr[data-id]');
        $this->assertCount(2, $rows, 'Table should have entity rows');
        $this->assertSame(0, $crawler->filter('td.actions')->count(), 'Actions cells should not be rendered when no row has actions');
        $this->assertSame(1, $crawler->filter('thead th')->count(), 'Only the configured field should have a header cell');
        $this->assertSame(1, $rows->first()->filter('td')->count(), 'Rows should only contain the configured field cell');
        $this->assertSelectorExists('td[data-column="name"]');
    }

    public function testActionsColumnIsShownWhenSomeRowsHaveActions(): void
    {
        // the controller only displays the edit action for entities with an even id
        $categories = CategoryFactory::createMany(2);
        [$categoryWithActions, $categoryWithoutActions] = 0 === $categories[0]->getId() % 2 ? $categories : array_reverse($categories);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, SomeRowsWithEntityActionsCrudController::class));

        $this->assertResponseIsSuccessful();

        $rows = $crawler->filter('tbody tr[data-id]');
        $this->assertCount(2, $rows, 'Table should have entity rows');
        $this->assertSame(2, $crawler->filter('thead th')->count(), 'The actions header cell should be rendered next to the field header');
        $this->assertCount(2, $crawler->filter('td.actions'), 'Every row should have an actions cell, even the rows without actions');

        $this->assertCount(1, $crawler->filter('td.actions .action-edit'), 'Only one row should display the edit action');
        $this->assertIndexEntityActionExists(Action::EDIT, $categoryWithActions->getId());
        $this->assertIndexEntityActionNotExists(Action::EDIT, $categoryWithoutActions->getId());
    }

    public function testSearchFormExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that search form exists
        $this->assertSelectorExists('form.form-action-search', 'Search form should be present');
        $this->assertSelectorExists('input[name="query"]', 'Search input should be present');
    }

    public function testNewActionLinkWorks(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // find and click the New action
        $newLink = $crawler->filter('.global-actions .action-new');
        $this->assertCount(1, $newLink, 'New action link should exist');

        $this->client->click($newLink->link());
        $this->assertResponseIsSuccessful();

        // verify we are on the new form page
        $this->assertSelectorExists('form', 'New page should display a form');
    }

    public function testPaginationWorksWithMultiplePages(): void
    {
        // the default page size is 20, so 21 entities need two pages
        CategoryFactory::createMany(21);

        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertIndexFullEntityCount(21);
        $this->assertIndexPageEntityCount(20);
        $this->assertIndexPagesCount(2);
    }
}
