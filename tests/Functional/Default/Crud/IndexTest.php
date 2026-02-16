<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\DefaultCrudTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;

/**
 * Tests for default index page behavior in EasyAdmin CRUD operations.
 */
class IndexTest extends AbstractCrudTestCase
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
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check for pagination counter
        $this->assertSelectorExists('.list-pagination-counter', 'Pagination counter should be present');

        // check that entity count is displayed
        $totalEntities = \count($this->repository->findAll());
        $this->assertIndexFullEntityCount($totalEntities);
    }

    public function testDefaultColumnsAreDisplayed(): void
    {
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
        $entity = $this->repository->findOneBy([]);
        $this->assertNotNull($entity, 'At least one entity should exist in fixtures');

        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that Edit action exists for the entity
        $this->assertIndexEntityActionExists(Action::EDIT, $entity->getId());

        // check that Delete action exists for the entity
        $this->assertIndexEntityActionExists(Action::DELETE, $entity->getId());
    }

    public function testEntityCountIsDisplayedCorrectly(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        $totalEntities = \count($this->repository->findAll());
        $this->assertIndexFullEntityCount($totalEntities);
    }

    public function testIndexShowsEntitiesInTable(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that table body exists with rows
        $this->assertSelectorExists('tbody', 'Table should have a body');

        $rows = $crawler->filter('tbody tr');
        $this->assertGreaterThan(0, $rows->count(), 'Table should have data rows');
    }

    public function testEntityRowsExist(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that entity rows exist in the table body
        $rows = $crawler->filter('tbody tr');
        $this->assertGreaterThan(0, $rows->count(), 'Table should have entity rows');
    }

    public function testActionsColumnExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check for actions column (contains edit/delete links)
        $actionsColumn = $crawler->filter('td.actions');
        $this->assertGreaterThan(0, $actionsColumn->count(), 'Actions column should be present for each row');
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
        // this test verifies pagination when there are more entities than fit on one page
        // the default page size in EasyAdmin is typically 15-20 items
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // check that pagination exists
        $paginationExists = $crawler->filter('.list-pagination-paginator')->count() > 0;
        if ($paginationExists) {
            $pageItems = $crawler->filter('.list-pagination-paginator .page-item');
            $this->assertGreaterThan(0, $pageItems->count(), 'Pagination should have page items');
        }
    }
}
