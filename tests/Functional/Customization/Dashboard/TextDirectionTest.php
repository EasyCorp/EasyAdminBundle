<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\TextDirectionTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DemoEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Dashboard::setTextDirection() configuration method.
 */
class TextDirectionTest extends AbstractCrudTestCase
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
        return TextDirectionTestDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testTextDirectionIsRTL(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify the html or body tag has dir="rtl"
        $html = $crawler->filter('html, body');
        $dir = $html->attr('dir');
        static::assertSame('rtl', $dir, 'Text direction should be set to RTL');
    }
}
