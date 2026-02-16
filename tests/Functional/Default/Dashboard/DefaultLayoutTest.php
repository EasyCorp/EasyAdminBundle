<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;

/**
 * Tests for default layout options in the dashboard.
 */
class DefaultLayoutTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testDefaultContentWidthIsNormal(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // by default, content width is 'normal'
        $body = $crawler->filter('body');
        static::assertSame('normal', $body->attr('data-ea-content-width'));
    }

    public function testDefaultSidebarWidthIsNormal(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // by default, sidebar width is 'normal'
        $body = $crawler->filter('body');
        static::assertSame('normal', $body->attr('data-ea-sidebar-width'));
    }

    public function testDefaultTextDirectionIsLtr(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // by default, text direction is 'ltr'
        $html = $crawler->filter('html');
        static::assertSame('ltr', $html->attr('dir'));
    }

    public function testDefaultFaviconIsUsedWhenNotCustomized(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // default favicon should be present (using data URI)
        $favicon = $crawler->filter('link[rel="shortcut icon"]');
        static::assertGreaterThan(0, $favicon->count());
    }

    public function testDefaultDashboardTitle(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the dashboard title should appear in the logo area
        $logo = $crawler->filter('.logo .logo-custom');
        static::assertGreaterThan(0, $logo->count());
        static::assertStringContainsString('EasyAdmin Tests', $logo->text());
    }
}
