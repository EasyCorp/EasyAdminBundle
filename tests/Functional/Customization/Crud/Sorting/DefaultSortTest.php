<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Sorting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Sorting\DefaultSortTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setDefaultSort() configuration method.
 */
class DefaultSortTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DefaultSortTestCrudController::class;
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

    public function testDefaultSortConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // with id DESC sort, first rows should have highest IDs
        $rows = $crawler->filter('tbody tr')->slice(0, 3);
        $ids = [];

        $rows->each(static function ($row) use (&$ids) {
            $idCell = $row->filter('td[data-column="id"]');
            if ($idCell->count() > 0) {
                $ids[] = (int) trim($idCell->text());
            }
        });

        // with DESC sort on id, first ID should be greater than second
        if (\count($ids) >= 2) {
            static::assertGreaterThan($ids[1], $ids[0], 'First row ID should be greater than second (DESC sort)');
        }

        // verify pagination exists
        static::assertSelectorExists('.list-pagination');
    }
}
