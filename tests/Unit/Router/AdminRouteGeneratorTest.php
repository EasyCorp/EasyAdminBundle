<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\BuiltInActionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\FooController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\SecondDashboardController;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\Filesystem\Filesystem;

class AdminRouteGeneratorTest extends KernelTestCase
{
    /**
     * @dataProvider provideFindRouteData
     */
    public function testFindRoute(?string $dashboardControllerFqcn, ?string $crudControllerFqcn, ?string $action, ?string $expectedRouteName): void
    {
        self::bootKernel();

        $cacheMock = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $cacheMock->method('getItem')->willReturnCallback(static function ($key) {
            $item = new CacheItem();
            $item->expiresAfter(3600);

            if (AdminRouteGenerator::CACHE_KEY_FQCN_TO_ROUTE !== $key) {
                return $item;
            }

            $item->set([
                DashboardController::class => [
                    '' => [
                        '' => 'admin',
                    ],
                    BuiltInActionCrudController::class => [
                        'index' => 'admin_crud_index',
                        'new' => 'admin_crud_new',
                        'edit' => 'admin_crud_edit',
                        'detail' => 'admin_crud_detail',
                    ],
                ],
                SecondDashboardController::class => [
                    '' => [
                        '' => 'second_admin',
                    ],
                ],
            ]);

            return $item;
        });

        $dashboardControllers = new RewindableGenerator(static function () {
            yield DashboardController::class => new DashboardController();
            yield SecondDashboardController::class => new SecondDashboardController();
        }, 2);

        $adminRouteGenerator = new AdminRouteGenerator(
            $dashboardControllers,
            [],
            $cacheMock,
            new Filesystem(),
            self::$kernel->getBuildDir(),
        );

        $routeName = $adminRouteGenerator->findRouteName($dashboardControllerFqcn, $crudControllerFqcn, $action);
        $this->assertSame($expectedRouteName, $routeName);
    }

    public static function provideFindRouteData(): iterable
    {
        yield [null, null, null, 'admin'];
        yield [DashboardController::class, null, null, 'admin'];
        yield [DashboardController::class, BuiltInActionCrudController::class, null, null];
        yield [DashboardController::class, BuiltInActionCrudController::class, 'index', 'admin_crud_index'];
        yield [DashboardController::class, BuiltInActionCrudController::class, 'detail', 'admin_crud_detail'];
        yield [DashboardController::class, FooController::class, null, null];
        yield [DashboardController::class, FooController::class, 'index', null];
        yield [DashboardController::class, FooController::class, 'detail', null];
        yield [SecondDashboardController::class, null, null, 'second_admin'];
        yield [SecondDashboardController::class, BuiltInActionCrudController::class, null, null];
        yield [SecondDashboardController::class, BuiltInActionCrudController::class, 'index', null];
        yield [SecondDashboardController::class, BuiltInActionCrudController::class, 'detail', null];
    }
}
