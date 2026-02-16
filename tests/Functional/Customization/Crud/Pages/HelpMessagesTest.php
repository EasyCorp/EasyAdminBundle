<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pages;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pages\HelpMessagesTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setHelp() configuration method.
 */
class HelpMessagesTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return HelpMessagesTestCrudController::class;
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

    public function testIndexPageHelpMessage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        $pageContent = $crawler->html();
        static::assertStringContainsString('This is the index page help message', $pageContent);

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);
    }

    public function testNewPageHelpMessage(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();

        $pageContent = $crawler->html();
        static::assertStringContainsString('This is the new page help message', $pageContent);

        // verify form exists
        static::assertSelectorExists('form');
    }
}
