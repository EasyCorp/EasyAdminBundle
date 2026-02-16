<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\LayoutDashboardController;

/**
 * Tests for customized layout options in the dashboard:
 * - renderContentMaximized
 * - renderSidebarMinimized
 * - setTextDirection (RTL/LTR)
 * - setFaviconPath.
 */
class LayoutTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return LayoutDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testContentMaximizedChangesDataAttribute(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when renderContentMaximized() is called, the content width becomes 'full'
        $body = $crawler->filter('body');
        static::assertSame('full', $body->attr('data-ea-content-width'));
    }

    public function testSidebarMinimizedChangesDataAttribute(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when renderSidebarMinimized() is called, the sidebar width becomes 'compact'
        $body = $crawler->filter('body');
        static::assertSame('compact', $body->attr('data-ea-sidebar-width'));
    }

    public function testRtlTextDirectionIsApplied(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the LayoutDashboardController sets text direction to RTL
        $html = $crawler->filter('html');
        static::assertSame('rtl', $html->attr('dir'));
    }

    public function testCustomFaviconPathIsApplied(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the LayoutDashboardController sets a custom favicon
        $favicon = $crawler->filter('link[rel="shortcut icon"]');
        static::assertGreaterThan(0, $favicon->count());
        static::assertStringContainsString('favicon-custom.ico', $favicon->attr('href'));
    }

    public function testCombinedLayoutOptionsWork(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the LayoutDashboardController sets multiple options
        $body = $crawler->filter('body');
        $html = $crawler->filter('html');

        // verify all layout options are applied together
        static::assertSame('full', $body->attr('data-ea-content-width'), 'Content should be maximized');
        static::assertSame('compact', $body->attr('data-ea-sidebar-width'), 'Sidebar should be minimized');
        static::assertSame('rtl', $html->attr('dir'), 'Text direction should be RTL');
    }

    public function testDashboardTitleAppearsInLogo(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the dashboard title should appear in the logo area
        $logo = $crawler->filter('.logo .logo-custom');
        static::assertGreaterThan(0, $logo->count());
        static::assertStringContainsString('Layout Test Dashboard', $logo->text());
    }

    public function testBodyHasEaClass(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the body should have the 'ea' class
        $body = $crawler->filter('body.ea');
        static::assertCount(1, $body);
    }

    public function testLayoutContainsMainStructuralElements(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify the main layout structure
        static::assertGreaterThan(0, $crawler->filter('.wrapper')->count(), 'Wrapper should exist');
        static::assertGreaterThan(0, $crawler->filter('.sidebar-wrapper')->count(), 'Sidebar wrapper should exist');
        static::assertGreaterThan(0, $crawler->filter('.sidebar')->count(), 'Sidebar should exist');
        static::assertGreaterThan(0, $crawler->filter('.main-content')->count(), 'Main content should exist');
    }

    public function testResponsiveHeaderExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the responsive header should exist for mobile devices
        static::assertGreaterThan(0, $crawler->filter('.responsive-header')->count());
        static::assertGreaterThan(0, $crawler->filter('#navigation-toggler')->count());
    }

    public function testMetaRobotsTagExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the robots meta tag should prevent indexing
        $robotsMeta = $crawler->filter('meta[name="robots"]');
        static::assertGreaterThan(0, $robotsMeta->count());
        static::assertStringContainsString('noindex', $robotsMeta->attr('content'));
        static::assertStringContainsString('nofollow', $robotsMeta->attr('content'));
    }

    public function testMetaGeneratorTagExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the generator meta tag should indicate EasyAdmin
        $generatorMeta = $crawler->filter('meta[name="generator"]');
        static::assertGreaterThan(0, $generatorMeta->count());
        static::assertSame('EasyAdmin', $generatorMeta->attr('content'));
    }
}
