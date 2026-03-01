<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\LocaleDashboardController;

/**
 * Tests for locale switching functionality in the dashboard.
 */
class LocaleTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return LocaleDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testLocaleDropdownExistsWhenLocalesConfigured(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // settings dropdown should exist when locales are configured
        static::assertGreaterThan(0, $crawler->filter('.dropdown-settings')->count());
    }

    public function testLocaleDropdownShowsConfiguredLocales(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // check that all configured locales are present in the dropdown
        $html = $crawler->html();

        // simple locale codes get their native names from Symfony Intl
        static::assertStringContainsString('English', $html);
        static::assertStringContainsString('_locale=es', $html); // Spanish locale link
        static::assertStringContainsString('_locale=fr', $html); // French locale link
        static::assertStringContainsString('Deutsch', $html);     // Custom label for German
    }

    public function testLocaleDropdownShowsLocaleLabelHeader(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the locale section should have a header label
        $localeLabel = $crawler->filter('.dropdown-locales-label');
        static::assertGreaterThan(0, $localeLabel->count());
    }

    public function testCurrentLocaleIsMarkedAsActive(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the current locale (en by default) should have the 'active' class
        $activeLocale = $crawler->filter('.dropdown-settings .active');
        static::assertGreaterThan(0, $activeLocale->count());
    }

    /**
     * @testWith ["es"]
     *           ["fr"]
     *           ["de"]
     */
    public function testLocaleLinksContainCorrectLocaleParameter(string $locale): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // each locale should have a link with the correct _locale parameter
        $localeLink = $crawler->filter(sprintf('a[href*="_locale=%s"]', $locale));
        static::assertGreaterThan(0, $localeLink->count(), sprintf('Locale link for "%s" should exist', $locale));
    }

    public function testLocaleWithCustomIconShowsIcon(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the German locale was configured with an icon (fa fa-flag)
        $html = $crawler->html();
        static::assertStringContainsString('fa-flag', $html);
    }

    public function testHtmlLangAttributeMatchesCurrentLocale(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the html element should have the correct lang attribute
        $htmlElement = $crawler->filter('html');
        static::assertStringContainsString('en', $htmlElement->attr('lang'));
    }
}
