<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UiKit;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * This app doesn't set the parameter that enables the UI Kit, so the
 * UI Kit only exists in debug mode.
 */
class UiKitDebugModeTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testUiKitRoutesAreCreatedInDebugMode(): void
    {
        self::bootKernel(['debug' => true]);
        $routes = static::getContainer()->get('router')->getRouteCollection();

        $this->assertSame('/admin/_ea_ui_kit', $routes->get('admin_ea_ui_kit_index')?->getPath());
        $this->assertSame('/admin/_ea_ui_kit/components/{name}', $routes->get('admin_ea_ui_kit_component')?->getPath());
    }

    public function testUiKitRoutesAreNotCreatedWithoutDebugMode(): void
    {
        self::bootKernel(['debug' => false]);
        $routes = static::getContainer()->get('router')->getRouteCollection();

        $this->assertNull($routes->get('admin_ea_ui_kit_index'));
        $this->assertNull($routes->get('admin_ea_ui_kit_component'));
    }
}
