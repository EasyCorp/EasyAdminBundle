<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Search\SearchFieldsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\DemoEntityFactory;
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
        $alpha = DemoEntityFactory::createOne(['name' => 'Alpha item']);
        $beta = DemoEntityFactory::createOne(['name' => 'Beta item']);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed before search
        $this->assertIndexPageEntityCount(2);
        static::assertIndexFullEntityCount(2);

        // verify search input is present
        $searchInput = $crawler->filter('input[type="search"], input.search, [data-ea-search-input]');
        static::assertGreaterThan(0, $searchInput->count(), 'Search input should be present');

        // search by the 'name' field
        $this->client->request('GET', $this->generateIndexUrl('Alpha'));

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(1);
        static::assertSelectorExists($this->getIndexEntityRowSelector($alpha->getId()));

        // search by the 'id' field (entity names contain no digits, so only the id can match)
        $this->client->request('GET', $this->generateIndexUrl((string) $beta->getId()));

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(1);
        static::assertSelectorExists($this->getIndexEntityRowSelector($beta->getId()));
    }
}
