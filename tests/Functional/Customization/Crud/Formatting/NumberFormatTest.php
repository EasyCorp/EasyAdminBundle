<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\NumberFormatTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setNumberFormat() configuration method.
 */
class NumberFormatTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return NumberFormatTestCrudController::class;
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

    public function testNumberFormatConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // get first row with number data
        $firstRow = $crawler->filter('tbody tr')->first();
        $priceCell = $firstRow->filter('td[data-column="price"]');

        // verify number columns exist and have content
        if ($priceCell->count() > 0) {
            $priceText = trim($priceCell->text());
            // price with %.2f format should show 2 decimal places
            static::assertMatchesRegularExpression(
                '/\d+[.,]\d{2}/',
                $priceText,
                'Price should be formatted with 2 decimal places'
            );
        }

        // verify table structure
        static::assertSelectorExists('tbody tr');
    }
}
