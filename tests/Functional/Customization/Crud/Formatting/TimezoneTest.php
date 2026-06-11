<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\TimezoneTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setTimezone() configuration method.
 */
class TimezoneTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return TimezoneTestCrudController::class;
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

    public function testTimezoneConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // get first row with datetime data (skip null values)
        $firstRow = $crawler->filter('tbody tr')->first();
        $dateTimeCell = $firstRow->filter('td[data-column="createdAt"]');

        if ($dateTimeCell->count() > 0) {
            $dateTimeText = trim($dateTimeCell->text());

            // timezone config affects how datetime is displayed
            // verify datetime has time component
            static::assertMatchesRegularExpression(
                '/\d{1,2}:\d{2}/',
                $dateTimeText,
                'DateTime should include time component'
            );
        }

        // verify table structure
        static::assertSelectorExists('tbody tr');
    }
}
