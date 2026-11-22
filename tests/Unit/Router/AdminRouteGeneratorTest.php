<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\CacheKey;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\AdvancedOptionsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\BuiltInActionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\FooController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\SecondDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\FooBatchCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\FooBatchResolvedCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\FooCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\InterfaceOnlyRouteDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\InvalidDetailPathCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\InvalidDetailPathDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\RootPathDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\WidgetParameterDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\WidgetParameterWithWidgetsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures\WidgetRouteNameDashboardController;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

class AdminRouteGeneratorTest extends KernelTestCase
{
    #[DataProvider('provideFindRouteData')]
    public function testFindRoute(?string $dashboardControllerFqcn, ?string $crudControllerFqcn, ?string $action, ?string $expectedRouteName): void
    {
        self::bootKernel();

        $cacheMock = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $cacheMock->method('getItem')->willReturnCallback(static function ($key) {
            $item = new CacheItem();
            $item->expiresAfter(3600);

            if (CacheKey::ROUTE_ATTRIBUTES_TO_NAME !== $key) {
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
        );

        $routeName = $adminRouteGenerator->findRouteName($dashboardControllerFqcn, $crudControllerFqcn, $action);
        $this->assertSame($expectedRouteName, $routeName);
    }

    #[DataProvider('provideEntityIdPlaceholderData')]
    public function testGetEntityIdPlaceholderName(string $routePath, ?string $expectedPlaceholderName): void
    {
        $adminRouteGenerator = new AdminRouteGenerator(
            [],
            [],
            $this->getMockBuilder(CacheItemPoolInterface::class)->getMock(),
        );

        $method = new \ReflectionMethod($adminRouteGenerator, 'getEntityIdPlaceholderName');

        $this->assertSame($expectedPlaceholderName, $method->invoke($adminRouteGenerator, $routePath));
    }

    public function testDashboardRoutesConfigWithoutEntityIdPlaceholderThrows(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator(
            [new InvalidDetailPathDashboardController()],
            [],
            $this->getMockBuilder(CacheItemPoolInterface::class)->getMock(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/must contain the "\{id\}" or "\{entityId\}" placeholder/');

        $adminRouteGenerator->generateAll();
    }

    public function testAdminRouteAttributeWithoutEntityIdPlaceholderThrows(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator(
            [new DashboardController()],
            [new InvalidDetailPathCrudController()],
            $this->getMockBuilder(CacheItemPoolInterface::class)->getMock(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/missing the "\{id\}" or "\{entityId\}" placeholder/');

        $adminRouteGenerator->generateAll();
    }

    public static function provideEntityIdPlaceholderData(): iterable
    {
        yield 'plain id' => ['/{id}', 'id'];
        yield 'plain entityId' => ['/{entityId}', 'entityId'];
        yield 'mapped id' => ['/{id:product.id}', 'id'];
        yield 'mapped entityId' => ['/{entityId:product.id}', 'entityId'];
        yield 'constrained id' => ['/{id<\d+>}', 'id'];
        yield 'id in a longer path' => ['/safe-delete/{id}/confirm', 'id'];
        yield 'entityId in a longer path' => ['/{entityId}/safe-delete', 'entityId'];
        yield 'no placeholder' => ['/safe-delete', null];
        yield 'unrelated userId is not matched' => ['/{userId}', null];
        yield 'unrelated idCard is not matched' => ['/{idCard}', null];
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

    /**
     * When one entity name is a prefix of another (e.g. "Foo" and "FooBatch"), two
     * different CRUD controllers can generate the same route name (the "batchDelete"
     * action of FooCrudController and the "delete" action of FooBatchCrudController both
     * generate "admin_foo_batch_delete"). EasyAdmin must detect this and throw an
     * exception that names both colliding actions and explains how to fix it (#7654).
     */
    public function testCollidingRouteNamesThrowDescriptiveException(): void
    {
        $dashboardControllers = new RewindableGenerator(static function () {
            yield DashboardController::class => new DashboardController();
        }, 1);

        // FooCrudController is yielded first, so it claims "admin_foo_batch_delete" via its
        // "batchDelete" action and FooBatchCrudController's "delete" action triggers the collision
        $crudControllers = new RewindableGenerator(static function () {
            yield FooCrudController::class => new FooCrudController();
            yield FooBatchCrudController::class => new FooBatchCrudController();
        }, 2);

        $adminRouteGenerator = new AdminRouteGenerator($dashboardControllers, $crudControllers, new ArrayAdapter());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The route name "admin_foo_batch_delete" is generated by two different CRUD actions.*FooCrudController::batchDelete\(\).*FooBatchCrudController::delete\(\).*#\[AdminRoute\(name:/s');

        $adminRouteGenerator->generateAll();
    }

    /**
     * The collision detected in the test above is resolved by setting a custom route name
     * with the #[AdminRoute] attribute on one of the colliding controllers (#7654).
     */
    public function testCollidingRouteNamesAreResolvedWithAdminRouteAttribute(): void
    {
        $dashboardControllers = new RewindableGenerator(static function () {
            yield DashboardController::class => new DashboardController();
        }, 1);

        $crudControllers = new RewindableGenerator(static function () {
            yield FooCrudController::class => new FooCrudController();
            // this controller uses #[AdminRoute(name: 'foo_batches')], so its "delete" action
            // generates "admin_foo_batches_delete" instead of "admin_foo_batch_delete"
            yield FooBatchResolvedCrudController::class => new FooBatchResolvedCrudController();
        }, 2);

        $adminRouteGenerator = new AdminRouteGenerator($dashboardControllers, $crudControllers, new ArrayAdapter());

        $routes = $adminRouteGenerator->generateAll();

        $this->assertNotNull($routes->get('admin_foo_batch_delete'));
        $this->assertNotNull($routes->get('admin_foo_batches_delete'));
    }

    public function testWidgetRouteIsGeneratedForEachDashboard(): void
    {
        $cache = new ArrayAdapter();
        $adminRouteGenerator = new AdminRouteGenerator([new DashboardController(), new SecondDashboardController()], [], $cache);

        $routes = $adminRouteGenerator->generateAll();

        $widgetRoute = $routes->get('admin_widget');
        $this->assertNotNull($widgetRoute);
        $this->assertSame('/admin/_widget/{widgetName}', $widgetRoute->getPath());
        $this->assertSame(['GET'], $widgetRoute->getMethods());
        $this->assertSame('[a-zA-Z0-9_-]+', $widgetRoute->getRequirement('widgetName'));
        $this->assertSame(DashboardController::class.'::renderWidget', $widgetRoute->getDefault('_controller'));
        $this->assertSame(DashboardController::class, $widgetRoute->getDefault(EA::DASHBOARD_CONTROLLER_FQCN));
        $this->assertTrue($widgetRoute->getDefault(EA::ROUTE_CREATED_BY_EASYADMIN));
        $this->assertNull($widgetRoute->getDefault(EA::CRUD_CONTROLLER_FQCN));

        $this->assertSame('/second-admin/_widget/{widgetName}', $routes->get('second_admin_widget')?->getPath());

        // the widget route has the same attributes as the dashboard route, but it must not replace it as the dashboard URL
        $this->assertSame('admin', $adminRouteGenerator->findRouteName(DashboardController::class));
        $this->assertSame('second_admin', $adminRouteGenerator->findRouteName(SecondDashboardController::class));
        $this->assertSame(['admin_widget', 'second_admin_widget'], array_values(array_filter(
            array_keys($cache->getItem(CacheKey::ROUTE_NAME_TO_ATTRIBUTES)->get()),
            static fn (string $routeName): bool => str_ends_with($routeName, '_widget'),
        )), 'widget requests need the route attributes to create the admin context');
    }

    public function testWidgetRouteHasTheOptionsOfTheDashboardRoute(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator([new AdvancedOptionsDashboardController()], [], new ArrayAdapter());

        $widgetRoute = $adminRouteGenerator->generateAll()->get('advanced_admin_widget');

        $this->assertNotNull($widgetRoute);
        $this->assertSame('/advanced-admin/_widget/{widgetName}', $widgetRoute->getPath());
        $this->assertSame('admin.example.com', $widgetRoute->getHost());
        $this->assertSame(['https'], $widgetRoute->getSchemes());
        $this->assertSame('context.getMethod() in ["GET", "HEAD"]', $widgetRoute->getCondition());
        $this->assertSame(['GET'], $widgetRoute->getMethods());
    }

    public function testWidgetRouteOfDashboardWithRootPath(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator([new RootPathDashboardController()], [], new ArrayAdapter());

        $this->assertSame('/_widget/{widgetName}', $adminRouteGenerator->generateAll()->get('root_admin_widget')?->getPath());
    }

    public function testNoWidgetRouteWhenTheDashboardPathUsesItsPlaceholderAndThereAreNoWidgets(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator([new WidgetParameterDashboardController()], [], new ArrayAdapter());

        $routes = $adminRouteGenerator->generateAll();

        $this->assertSame('/admin/{widgetName}', $routes->get('widget_parameter_admin')?->getPath());
        $this->assertNull($routes->get('widget_parameter_admin_widget'));
        foreach ($routes as $route) {
            $route->compile();
        }
    }

    public function testDashboardWithWidgetsCantUseTheWidgetPlaceholderInItsPath(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator([new WidgetParameterWithWidgetsDashboardController()], [], new ArrayAdapter());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The route of the "%s" dashboard uses a placeholder called "{widgetName}", which is reserved for the route that serves the contents of its widgets. Rename that placeholder.', WidgetParameterWithWidgetsDashboardController::class));

        $adminRouteGenerator->generateAll();
    }

    public function testNoWidgetRouteForDashboardsWithoutRenderWidgetMethod(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator([new InterfaceOnlyRouteDashboardController()], [], new ArrayAdapter());

        $routes = $adminRouteGenerator->generateAll();

        $this->assertNotNull($routes->get('plain_admin'));
        $this->assertNull($routes->get('plain_admin_widget'));
    }

    public function testDashboardRouteNameCollidingWithAPreviousWidgetRoute(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator([new DashboardController(), new WidgetRouteNameDashboardController()], [], new ArrayAdapter());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The route name of the "%s" dashboard ("admin_widget") is the same as the name of the route that the "%s" dashboard generates to serve the contents of its widgets.', WidgetRouteNameDashboardController::class, DashboardController::class));

        $adminRouteGenerator->generateAll();
    }

    public function testWidgetRouteCollidingWithAPreviousDashboardRoute(): void
    {
        $adminRouteGenerator = new AdminRouteGenerator([new WidgetRouteNameDashboardController(), new DashboardController()], [], new ArrayAdapter());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The "%s" dashboard generates a route named "admin_widget" to serve the contents of its widgets, but that route name is already used', DashboardController::class));

        $adminRouteGenerator->generateAll();
    }
}
