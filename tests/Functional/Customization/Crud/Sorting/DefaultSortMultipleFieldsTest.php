<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Sorting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Sorting\DefaultSortMultipleFieldsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setDefaultSort() with multiple fields.
 */
class DefaultSortMultipleFieldsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DefaultSortMultipleFieldsTestCrudController::class;
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

    public function testDefaultSortWithMultipleFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // with name ASC, id DESC sort, verify rows are sorted by name alphabetically
        $rows = $crawler->filter('tbody tr')->slice(0, 5);
        $names = [];

        $rows->each(static function ($row) use (&$names) {
            $nameCell = $row->filter('td[data-column="name"]');
            if ($nameCell->count() > 0) {
                $names[] = trim($nameCell->text());
            }
        });

        // verify we have names to compare
        static::assertNotEmpty($names, 'Should have name values to verify sort');

        // with ASC sort on name, names should be in alphabetical order
        $sortedNames = $names;
        sort($sortedNames);
        static::assertSame($sortedNames, $names, 'Names should be sorted alphabetically (ASC)');

        // verify pagination exists
        static::assertSelectorExists('.list-pagination');
    }
}
