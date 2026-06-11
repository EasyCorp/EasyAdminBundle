<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\DateIntervalFormatTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setDateIntervalFormat() configuration method.
 */
class DateIntervalFormatTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DateIntervalFormatTestCrudController::class;
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

    public function testDateIntervalFormatConfiguration(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // dateInterval format config is set but DemoEntity has no DateInterval field
        // verify page renders correctly with the configuration
        $this->assertIndexPageEntityCount(20);

        // verify table structure exists
        static::assertSelectorExists('tbody tr');
        static::assertSelectorExists('.content');
    }
}
