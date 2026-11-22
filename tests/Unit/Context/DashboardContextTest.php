<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Context;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
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
        $context->setMainMenuBuilder(static function () use (&$builderCalled, $expectedMenu) {
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
        $context->setMainMenuBuilder(static function () use (&$callCount) {
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

    public function testExtraAssetsAreLazyLoadedAndMergedOnce(): void
    {
        $callCount = 0;
        $dashboardAssets = new AssetsDto();
        $dashboardAssets->addCssAsset(new AssetDto('dashboard.css'));

        $context = DashboardContext::forTesting(assets: $dashboardAssets);
        $context->setExtraAssetsBuilder(static function () use (&$callCount): AssetsDto {
            ++$callCount;
            $extraAssets = new AssetsDto();
            $extraAssets->addCssAsset(new AssetDto('widget.css'));
            $extraAssets->addJsAsset(new AssetDto('widget.js'));

            return $extraAssets;
        });

        self::assertSame(0, $callCount);

        $assets = $context->getAssets();
        $context->getAssets();

        self::assertSame(1, $callCount);
        self::assertSame(['dashboard.css', 'widget.css'], array_keys($assets->getCssAssets()));
        self::assertSame(['widget.js'], array_keys($assets->getJsAssets()));
    }

    public function testExtraAssetsBuilderCanReadTheAssets(): void
    {
        $context = DashboardContext::forTesting();
        $context->setExtraAssetsBuilder(static function () use (&$context): AssetsDto {
            self::assertSame([], $context->getAssets()->getCssAssets());

            $extraAssets = new AssetsDto();
            $extraAssets->addCssAsset(new AssetDto('widget.css'));

            return $extraAssets;
        });

        self::assertSame(['widget.css'], array_keys($context->getAssets()->getCssAssets()));
    }
}
