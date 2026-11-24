<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pages;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pages\FunctionNameStringsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\DemoEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests that the strings used as page titles and action URLs are never called
 * as callables, even if they match the name of a PHP function.
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
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->getDemoEntityId()));

        static::assertResponseIsSuccessful();
        static::assertSame('Trim', trim($crawler->filter('h1.title')->text()));
    }

    public function testDetailPageTitleIsNotCalledWithTheEntity(): void
    {
        $crawler = $this->client->request('GET', $this->generateDetailUrl($this->getDemoEntityId()));

        static::assertResponseIsSuccessful();
        static::assertSame('Trim', trim($crawler->filter('h1.title')->text()));
    }

    public function testActionUrlIsNotCalledWithTheEntity(): void
    {
        $crawler = $this->client->request('GET', $this->generateDetailUrl($this->getDemoEntityId()));

        static::assertResponseIsSuccessful();
        static::assertSame('reset', $crawler->filter('.action-reset')->attr('href'));
    }

    private function getDemoEntityId(): int
    {
        return $this->entityManager->getRepository(DemoEntity::class)->findOneBy([])->getId();
    }
}
