<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\TranslationDomainTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Dashboard::setTranslationDomain() configuration method.
 */
class TranslationDomainTest extends AbstractCrudTestCase
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
        return TranslationDomainTestDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testTranslationDomainIsConfigured(): void
    {
        CategoryFactory::createSequence([['name' => 'Category A'], ['name' => 'Category B']]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify page renders without translation errors
        $pageText = $crawler->filter('body')->text();
        static::assertStringNotContainsString('Translation not found', $pageText);
        static::assertStringNotContainsString('Missing translation', $pageText);

        // verify page structure exists
        static::assertSelectorExists('.content');

        // verify data is displayed
        $this->assertIndexPageEntityCount(2);
        static::assertStringContainsString('Category A', $pageText);
        static::assertStringContainsString('Category B', $pageText);
    }
}
