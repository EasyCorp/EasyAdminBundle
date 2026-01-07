<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Context;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use PHPUnit\Framework\TestCase;

class DashboardContextTest extends TestCase
{
    public function testMainMenuIsLazyLoaded(): void
    {
        $builderCalled = false;
        $expectedMenu = new MainMenuDto([new MenuItemDto()]);

        $dto = Dashboard::new()->getAsDto();
        $dto->setRouteName('admin');

        $context = new DashboardContext($dto, 'App\\Controller\\DashboardController', new AssetsDto(), false);
        $context->setMainMenuBuilder(function () use (&$builderCalled, $expectedMenu) {
            $builderCalled = true;

            return $expectedMenu;
        });

        self::assertFalse($builderCalled);

        $menu = $context->getMainMenu();

        self::assertTrue($builderCalled);
        self::assertSame($expectedMenu, $menu);
    }

    public function testMainMenuBuilderIsCalledOnlyOnce(): void
    {
        $callCount = 0;

        $dto = Dashboard::new()->getAsDto();
        $dto->setRouteName('admin');

        $context = new DashboardContext($dto, 'App\\Controller\\DashboardController', new AssetsDto(), false);
        $context->setMainMenuBuilder(function () use (&$callCount) {
            ++$callCount;

            return new MainMenuDto([]);
        });

        $context->getMainMenu();
        $context->getMainMenu();
        $context->getMainMenu();

        self::assertSame(1, $callCount);
    }

    public function testForTestingBypassesLazyLoading(): void
    {
        $menu = new MainMenuDto([new MenuItemDto()]);
        $context = DashboardContext::forTesting(mainMenu: $menu);

        self::assertSame($menu, $context->getMainMenu());
    }
}
