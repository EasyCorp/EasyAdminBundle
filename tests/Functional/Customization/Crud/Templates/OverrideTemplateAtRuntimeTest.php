<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Templates;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Templates\OverrideTemplateTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for AdminContext::overrideTemplate(), called by an event subscriber of the test app.
 */
class OverrideTemplateAtRuntimeTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return OverrideTemplateTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testTemplateIsOverriddenForTheCurrentRequest(): void
    {
        CategoryFactory::createMany(2);

        $this->client->request('GET', $this->generateIndexUrl(), server: ['HTTP_X_OVERRIDE_LAYOUT' => '1']);

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('body[data-override-layout="true"]');
        $this->assertIndexPageEntityCount(2);
    }

    public function testTemplateIsNotOverriddenForOtherRequests(): void
    {
        CategoryFactory::createMany(2);

        $this->client->request('GET', $this->generateIndexUrl(), server: ['HTTP_X_OVERRIDE_LAYOUT' => '1']);
        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('body[data-override-layout]');
        $this->assertIndexPageEntityCount(2);
    }
}
