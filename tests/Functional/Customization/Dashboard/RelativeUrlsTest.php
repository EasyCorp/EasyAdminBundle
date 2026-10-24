<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\RelativeUrlsTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Dashboard::generateRelativeUrls() configuration method.
 */
class RelativeUrlsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return RelativeUrlsTestDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testRelativeUrlsAreGenerated(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // check that menu links are relative (not absolute with http://)
        $menuLinks = $crawler->filter('.ea-sidebar-item a, nav a');

        static::assertGreaterThan(0, $menuLinks->count());
        $firstHref = $menuLinks->first()->attr('href');

        // relative URLs should not start with http:// or https://
        static::assertStringNotContainsString('http://', $firstHref ?? '');
        static::assertStringNotContainsString('https://', $firstHref ?? '');
    }
}
