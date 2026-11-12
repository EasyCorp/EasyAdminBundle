<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pages;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pages\FunctionNameStringsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests that the strings used as page titles, tab badges and action URLs are
 * never called as callables, even if they match the name of a PHP function.
 */
class FunctionNameStringsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return FunctionNameStringsTestCrudController::class;
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

    public function testIndexPageTitleIsNotCalled(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        static::assertSame('Time', trim($crawler->filter('h1.title')->text()));
    }

    public function testNewPageTitleIsNotCalled(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();
        static::assertSame('Time', trim($crawler->filter('h1.title')->text()));
    }

    public function testEditPageTitleIsNotCalledWithTheEntity(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Some Category']);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($category->getId()));

        static::assertResponseIsSuccessful();
        static::assertSame('Trim', trim($crawler->filter('h1.title')->text()));
    }

    public function testDetailPageTitleIsNotCalledWithTheEntity(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Some Category']);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($category->getId()));

        static::assertResponseIsSuccessful();
        static::assertSame('Trim', trim($crawler->filter('h1.title')->text()));
    }

    public function testTabBadgeContentIsNotCalledWithTheEntity(): void
    {
        $category = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($category->getId()));

        static::assertResponseIsSuccessful();
        static::assertSame('Count', trim($crawler->filter('.tab-nav-item-badge')->text()));
    }

    public function testActionUrlIsNotCalledWithTheEntity(): void
    {
        $category = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($category->getId()));

        static::assertResponseIsSuccessful();
        static::assertSame('reset', $crawler->filter('.action-reset')->attr('href'));
    }
}
