<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\ColorSchemeDashboardController;

/**
 * Tests for color scheme (light/dark mode) functionality in the dashboard.
 */
class ColorSchemeTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return ColorSchemeDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testDarkColorSchemeIsApplied(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the ColorSchemeDashboardController sets dark as default
        $body = $crawler->filter('body');
        static::assertSame('dark', $body->attr('data-ea-default-color-scheme'));
    }

    public function testDarkModeEnabledByDefault(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // dark mode should be enabled by default
        $body = $crawler->filter('body');
        static::assertSame('true', $body->attr('data-ea-dark-scheme-is-enabled'));
    }

    public function testAppearanceDropdownExistsWhenDarkModeEnabled(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when dark mode is enabled, the appearance dropdown should be present
        $appearanceLabel = $crawler->filter('.dropdown-appearance-label');
        static::assertGreaterThan(0, $appearanceLabel->count());
    }

    public function testAppearanceDropdownShowsAllThreeOptions(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // light, dark, and auto options should be present
        $lightOption = $crawler->filter('[data-ea-color-scheme="light"]');
        $darkOption = $crawler->filter('[data-ea-color-scheme="dark"]');
        $autoOption = $crawler->filter('[data-ea-color-scheme="auto"]');

        static::assertGreaterThan(0, $lightOption->count());
        static::assertGreaterThan(0, $darkOption->count());
        static::assertGreaterThan(0, $autoOption->count());
    }

    public function testAutoOptionIsMarkedAsActiveByDefault(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the auto option should be marked as active by default
        $autoOption = $crawler->filter('[data-ea-color-scheme="auto"].active');
        static::assertGreaterThan(0, $autoOption->count());
    }

    public function testSettingsDropdownHasCorrectStructure(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // settings dropdown should have a button
        $settingsButton = $crawler->filter('.dropdown-settings-button');
        static::assertGreaterThan(0, $settingsButton->count());
    }

    public function testDropdownAppearanceItemsExist(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the appearance items should exist (there are duplicates for responsive + desktop view)
        $appearanceItems = $crawler->filter('.dropdown-appearance-item');
        // there should be 6 items: 3 (light, dark, auto) x 2 (responsive + desktop)
        static::assertSame(6, $appearanceItems->count());
    }
}
