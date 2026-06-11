<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Display;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Display\HideNullValuesTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::hideNullValues() configuration method.
 */
class HideNullValuesTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return HideNullValuesTestCrudController::class;
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

    public function testHideNullValuesConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // with fixtures, every 3rd item (3, 6, 9, ...) has null price
        // when hideNullValues() is set, cells with null values should be empty (no "NULL" label)
        $rows = $crawler->filter('tbody tr');

        $foundNullCell = false;
        $rows->each(static function ($row) use (&$foundNullCell) {
            $priceCell = $row->filter('td[data-column="price"]');
            if ($priceCell->count() > 0) {
                $cellText = trim($priceCell->text());
                // if cell is empty, that's correct for null values with hideNullValues()
                if ('' === $cellText) {
                    $foundNullCell = true;
                }
                // verify no "NULL" label is displayed
                static::assertStringNotContainsStringIgnoringCase('null', $cellText, 'Null values should not display "NULL" label');
            }
        });

        // verify we found at least one empty cell (null value hidden)
        static::assertTrue($foundNullCell, 'Should have at least one empty cell for null price values');
    }
}
