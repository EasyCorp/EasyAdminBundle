<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\DateFormatTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setDateFormat() configuration method.
 */
class DateFormatTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DateFormatTestCrudController::class;
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

    public function testDateFormatConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // get first row with data (skip null values - every 3rd item has nulls)
        $firstRow = $crawler->filter('tbody tr')->first();
        $cells = $firstRow->filter('td[data-column="dateField"], td[data-column="createdAt"]');

        // verify date column has content with 'long' format (full month name like "June 15, 2024")
        $foundDateFormat = false;
        $cells->each(static function ($cell) use (&$foundDateFormat) {
            $text = trim($cell->text());
            // long format shows full month name followed by day and year
            if (preg_match('/[A-Z][a-z]+ \d{1,2}, \d{4}/', $text)) {
                $foundDateFormat = true;
            }
        });

        static::assertTrue($foundDateFormat, 'Date should be displayed in long format (e.g., "June 15, 2024")');
    }
}
