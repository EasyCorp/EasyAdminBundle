<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\DisabledDarkModeDashboardController;

/**
 * Tests for disabled dark mode functionality.
 */
class DisabledDarkModeTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DisabledDarkModeDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testDarkModeCanBeDisabled(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the DisabledDarkModeDashboardController disables dark mode
        $body = $crawler->filter('body');
        static::assertSame('false', $body->attr('data-ea-dark-scheme-is-enabled'));
    }

    public function testAppearanceDropdownNotVisibleWhenDarkModeDisabled(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when dark mode is disabled, the appearance dropdown should not exist
        $appearanceLabel = $crawler->filter('.dropdown-appearance-label');
        static::assertCount(0, $appearanceLabel);
    }

    public function testSettingsDropdownDoesNotShowAppearanceOptions(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when dark mode is disabled, there should be no color scheme items
        $colorSchemeItems = $crawler->filter('[data-ea-color-scheme]');
        static::assertCount(0, $colorSchemeItems);
    }
}
