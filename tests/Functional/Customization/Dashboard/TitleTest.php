<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\TitleTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DemoEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Dashboard::setTitle() configuration method.
 */
class TitleTest extends AbstractCrudTestCase
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
        return TitleTestDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testCustomTitleIsRenderedInPageTitle(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify the dashboard title appears in the logo area
        $logo = $crawler->filter('.logo .logo-custom');
        static::assertGreaterThan(0, $logo->count());
        static::assertStringContainsString('Custom Dashboard Title', $logo->text());
    }

    public function testCustomTitleIsRenderedInHeader(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify the title appears in the main header/logo area
        $headerTitle = $crawler->filter('.main-header .logo-custom')->text();
        static::assertStringContainsString('Custom Dashboard Title', $headerTitle);
    }

    public function testDashboardIndexShowsTitle(): void
    {
        // request the dashboard index page directly
        $crawler = $this->client->request('GET', '/customization_title_admin');

        static::assertResponseIsSuccessful();

        // verify the dashboard title appears in the logo area on the dashboard index
        $logo = $crawler->filter('.logo .logo-custom');
        static::assertGreaterThan(0, $logo->count());
        static::assertStringContainsString('Custom Dashboard Title', $logo->text());
    }
}
