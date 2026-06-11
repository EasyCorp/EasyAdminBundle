<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Search\SearchModeTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setSearchMode() configuration method.
 */
class SearchModeTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return SearchModeTestCrudController::class;
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

    public function testSearchModeConfiguration(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed before search
        $this->assertIndexPageEntityCount(20);
        static::assertIndexFullEntityCount(100);

        // test search with multiple terms (ALL_TERMS mode requires all terms to match)
        $this->client->request('GET', $this->generateIndexUrl('Demo Item'));

        static::assertResponseIsSuccessful();

        // verify results are returned (all items match "Demo Item")
        static::assertSelectorExists('tbody tr', 'Search should return results');
        static::assertSelectorNotExists('tr.no-results');
    }
}
