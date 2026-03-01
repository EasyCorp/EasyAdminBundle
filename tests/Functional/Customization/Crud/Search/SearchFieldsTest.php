<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Search\SearchFieldsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setSearchFields() configuration method.
 */
class SearchFieldsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return SearchFieldsTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testSearchFieldsConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed before search
        $this->assertIndexPageEntityCount(20);
        static::assertIndexFullEntityCount(100);

        // verify search input is present
        $searchInput = $crawler->filter('input[type="search"], input.search, [data-ea-search-input]');
        static::assertGreaterThan(0, $searchInput->count(), 'Search input should be present');

        // perform a search
        $this->client->request('GET', $this->generateIndexUrl('Demo Item 001'));

        static::assertResponseIsSuccessful();

        // search should filter results
        $rowCount = $this->client->getCrawler()->filter('tbody tr')->count();
        static::assertLessThan(100, $rowCount, 'Search should filter results');
    }
}
