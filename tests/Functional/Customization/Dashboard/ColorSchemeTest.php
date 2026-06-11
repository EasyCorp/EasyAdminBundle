<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\ColorSchemeTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DemoEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Dashboard::setDefaultColorScheme() configuration method.
 */
class ColorSchemeTest extends AbstractCrudTestCase
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
        return ColorSchemeTestDashboardController::class;
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

        // verify the dark color scheme is set as default
        $body = $crawler->filter('body');
        static::assertSame('dark', $body->attr('data-ea-default-color-scheme'));
    }

    public function testColorSchemeAppliesToAllPages(): void
    {
        $crawler = $this->client->request('GET', '/customization_colorscheme_admin');

        static::assertResponseIsSuccessful();

        $body = $crawler->filter('body');
        static::assertSame('dark', $body->attr('data-ea-default-color-scheme'));
    }
}
