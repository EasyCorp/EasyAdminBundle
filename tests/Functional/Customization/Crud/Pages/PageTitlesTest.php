<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pages;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pages\PageTitlesTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setPageTitle() configuration method.
 */
class PageTitlesTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return PageTitlesTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testCustomIndexPageTitle(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        $pageContent = $crawler->html();
        static::assertStringContainsString('Custom Index Title', $pageContent);

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);
    }

    public function testCustomNewPageTitle(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();

        $pageContent = $crawler->html();
        static::assertStringContainsString('Custom New Title', $pageContent);

        // verify form exists
        static::assertSelectorExists('form');
    }
}
