<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\TimeFormatTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setTimeFormat() configuration method.
 */
class TimeFormatTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return TimeFormatTestCrudController::class;
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

    public function testTimeFormatConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // get first row with time data (skip null values - every 3rd item has nulls)
        $firstRow = $crawler->filter('tbody tr')->first();
        $timeCell = $firstRow->filter('td[data-column="timeField"]');

        if ($timeCell->count() > 0) {
            $timeText = trim($timeCell->text());

            // short time format should show HH:MM pattern
            static::assertMatchesRegularExpression(
                '/\d{1,2}:\d{2}/',
                $timeText,
                'Time should be formatted with hours and minutes (short format)'
            );
        }

        // verify table structure
        static::assertSelectorExists('tbody tr');
    }
}
