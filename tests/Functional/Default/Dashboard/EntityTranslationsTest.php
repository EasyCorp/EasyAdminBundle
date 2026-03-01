<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\EntityTranslationsDashboardController;

/**
 * Tests for entity translations functionality in the dashboard.
 */
class EntityTranslationsTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return EntityTranslationsDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testFieldLabelsUseEntityTranslationKeys(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        $html = $crawler->html();

        // when entity translations are enabled, field labels should use translation keys
        // the translation file defines labels with "(from file)" suffix
        static::assertStringContainsString('Name (from file)', $html);
        static::assertStringContainsString('Slug (from file)', $html);
        static::assertStringContainsString('Active (from file)', $html);
    }

    public function testTranslatedLabelsAppearedInTableHeaders(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // check that translated labels appear in table headers
        $tableHeaders = $crawler->filter('table.datagrid thead th');

        $headerTexts = [];
        $tableHeaders->each(static function ($node) use (&$headerTexts) {
            $headerTexts[] = trim($node->text());
        });

        // the headers should contain our translated field names
        static::assertContains('Name (from file)', $headerTexts);
        static::assertContains('Slug (from file)', $headerTexts);
    }

    public function testMenuItemUsesEntityPluralTranslation(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when menu item label is null, it should use the entity's plural translation
        $menuItems = $crawler->filter('.menu-item a');
        $menuTexts = [];
        $menuItems->each(static function ($node) use (&$menuTexts) {
            $menuTexts[] = trim($node->text());
        });

        static::assertContains('Categories (plural from file)', $menuTexts);
    }

    public function testPageTitleUsesEntityPluralTranslation(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the index page title should use the entity's plural translation
        $pageTitle = $crawler->filter('.content-header-title h1.title')->text();

        static::assertStringContainsString('Categories (plural from file)', $pageTitle);
    }

    public function testAddActionButtonUsesEntitySingularTranslation(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the "Add" action button should use the entity's singular translation
        $html = $crawler->html();

        static::assertStringContainsString('Category (singular from file)', $html);
    }
}
