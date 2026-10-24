<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pages;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pages\PageTitlesCallableTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for callable page titles in Crud configuration.
 */
class PageTitlesCallableTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return PageTitlesCallableTestCrudController::class;
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

    public function testCallableIndexPageTitle(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        $pageContent = $crawler->html();
        static::assertStringContainsString('Dynamic Index Title', $pageContent);

        // verify data is displayed
        $this->assertIndexPageEntityCount(2);
    }

    public function testCallableNewPageTitle(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();

        $pageContent = $crawler->html();
        static::assertStringContainsString('Dynamic New Title', $pageContent);

        // verify form exists
        static::assertSelectorExists('form');
    }
}
