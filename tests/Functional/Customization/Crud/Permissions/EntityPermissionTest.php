<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Permissions;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Permissions\EntityPermissionTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setEntityPermission() configuration method.
 */
class EntityPermissionTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return EntityPermissionTestCrudController::class;
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

    public function testEntityPermissionConfiguration(): void
    {
        // this test verifies the configuration doesn't cause errors during URL generation
        // actual permission testing would require a security setup
        $url = $this->generateIndexUrl();

        // verify URL was generated successfully
        static::assertNotEmpty($url, 'Index URL should be generated');
        static::assertStringContainsString('entity-permission', $url);
    }
}
