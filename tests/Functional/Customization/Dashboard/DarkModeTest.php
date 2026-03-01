<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\DarkModeTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DemoEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Dashboard::disableDarkMode() configuration method.
 */
class DarkModeTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DemoEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DarkModeTestDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testDarkModeIsDisabled(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify dark mode is disabled
        $body = $crawler->filter('body');
        static::assertSame('false', $body->attr('data-ea-dark-scheme-is-enabled'));
    }

    public function testAppearanceDropdownIsNotPresent(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when dark mode is disabled, the appearance dropdown should not be present
        $appearanceLabel = $crawler->filter('.dropdown-appearance-label');
        static::assertSame(0, $appearanceLabel->count());
    }
}
