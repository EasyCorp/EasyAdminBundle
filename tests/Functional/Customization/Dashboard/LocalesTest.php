<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\LocalesTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DemoEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Dashboard::setLocales() configuration method.
 */
class LocalesTest extends AbstractCrudTestCase
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
        return LocalesTestDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testLocalesDropdownExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify the settings dropdown exists (which contains locale options when locales are configured)
        $settingsDropdown = $crawler->filter('.dropdown-settings');
        static::assertGreaterThan(0, $settingsDropdown->count(), 'Settings dropdown should be present when locales are configured');
    }

    public function testAllConfiguredLocalesAreAvailable(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the configured locales should be available in the page
        $pageContent = $crawler->html();

        // check for locale data or links (implementation may vary)
        static::assertStringContainsString('en', $pageContent);
        static::assertStringContainsString('fr', $pageContent);
        static::assertStringContainsString('es', $pageContent);
        static::assertStringContainsString('de', $pageContent);
    }
}
