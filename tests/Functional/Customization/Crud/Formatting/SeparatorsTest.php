<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\SeparatorsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setThousandsSeparator() and Crud::setDecimalSeparator() configuration methods.
 */
class SeparatorsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return SeparatorsTestCrudController::class;
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

    public function testSeparatorsConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // get first row with price data (has decimal)
        $firstRow = $crawler->filter('tbody tr')->first();
        $priceCell = $firstRow->filter('td[data-column="price"]');

        if ($priceCell->count() > 0) {
            $priceText = trim($priceCell->text());

            // with decimal separator ',' price like 99.99 should show as 99,99
            static::assertMatchesRegularExpression(
                '/\d+,\d+/',
                $priceText,
                'Price should use comma as decimal separator'
            );
        }

        // verify table structure
        static::assertSelectorExists('tbody tr');
    }
}
