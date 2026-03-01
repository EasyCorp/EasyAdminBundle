<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;

/**
 * Tests for default color scheme configuration.
 */
class DefaultColorSchemeTest extends AbstractCrudTestCase
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

    public function testDefaultColorSchemeIsAuto(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // by default, the color scheme is 'auto'
        $body = $crawler->filter('body');
        static::assertSame('auto', $body->attr('data-ea-default-color-scheme'));
    }

    public function testDarkModeIsEnabledByDefault(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // by default, dark mode is enabled
        $body = $crawler->filter('body');
        static::assertSame('true', $body->attr('data-ea-dark-scheme-is-enabled'));
    }
}
