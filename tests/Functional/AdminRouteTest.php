<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Route;

class AdminRouteTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        if (version_compare(\Symfony\Component\HttpKernel\Kernel::VERSION,
            '5.4.1', '<')) {
            $this->markTestSkipped('AdminRoute attributes require Symfony 5.4.1 or higher');
        }

        parent::setUp();

        // Create client which boots the kernel
        $client = static::createClient();

        // Enable pretty URLs for this test
        $buildDir = $client->getKernel()->getContainer()->getParameter('kernel.build_dir');
        $filesystem = new Filesystem();
        $filesystem->touch($buildDir.'/easyadmin_pretty_urls_enabled');

        // Shutdown kernel to allow tests to create their own clients
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        // Clean up the pretty URLs marker file
        $filesystem = new Filesystem();
        $buildDir = sys_get_temp_dir().'/EasyAdminBundle/tests/AdminRouteTestApplication/var/cache';
        $filesystem->remove($buildDir.'/test/easyadmin_pretty_urls_enabled');

        parent::tearDown();
    }

    public function testInvokableControllerRoute(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // Test that the invokable controller route exists
        $route = $router->getRouteCollection()->get('admin_custom_invokable');
        $this->assertNotNull($route);
        $this->assertSame('/admin/custom-invokable', $route->getPath());

        // Test the controller action
        $defaults = $route->getDefaults();
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\InvokableController::__invoke',
            $defaults['_controller']
        );

        // Test the locale default
        $this->assertSame('en', $defaults['_locale']);

        // Test that the second dashboard also has the route
        $route2 = $router->getRouteCollection()->get('second_admin_custom_invokable');
        $this->assertNotNull($route2);
        $this->assertSame('/second-admin/custom-invokable', $route2->getPath());
    }

    public function testControllerWithClassAndMethodAttributes(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // Debug: List all admin routes
        $routes = $router->getRouteCollection();
        $adminRoutes = [];
        foreach ($routes as $name => $route) {
            if (str_contains($name, 'foo')) {
                $adminRoutes[$name] = $route->getPath();
            }
        }

        // Test the list route (should combine class + method)
        $listRoute = $router->getRouteCollection()->get('admin_foo_list');
        $this->assertNotNull($listRoute, 'Foo routes found: '.json_encode($adminRoutes));
        $this->assertSame('/admin/foo/list', $listRoute->getPath());
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\FooController::list',
            $listRoute->getDefault('_controller')
        );

        // Test the export CSV route
        $exportRoute = $router->getRouteCollection()->get('admin_foo_export_csv');
        $this->assertNotNull($exportRoute);
        $this->assertSame('/admin/foo/export/csv', $exportRoute->getPath());
        $this->assertContains('GET', $exportRoute->getMethods());
        $this->assertContains('POST', $exportRoute->getMethods());

        // Test public export route (overrides dashboard restrictions)
        $publicRoute = $router->getRouteCollection()->get('admin_foo_public_export');
        $this->assertNotNull($publicRoute);
        $this->assertSame('/admin/foo/public-export', $publicRoute->getPath());

        // The second dashboard should also have the public export route
        $publicRoute2 = $router->getRouteCollection()->get('second_admin_foo_public_export');
        $this->assertNotNull($publicRoute2);
        $this->assertSame('/second-admin/foo/public-export', $publicRoute2->getPath());

        // But the second dashboard should NOT have the restricted routes
        $this->assertNull($router->getRouteCollection()->get('second_admin_foo_list'));
        $this->assertNull($router->getRouteCollection()->get('second_admin_foo_export_csv'));
    }

    public function testControllerWithPartialClassConfiguration(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // ReportsController is restricted to SecondDashboardController
        // So it should NOT have routes for the main dashboard
        $this->assertNull($router->getRouteCollection()->get('admin_sales_report'));
        $this->assertNull($router->getRouteCollection()->get('admin_inventory_report'));

        // But it SHOULD have routes for the second dashboard
        $salesRoute = $router->getRouteCollection()->get('second_admin_sales_report');
        $this->assertNotNull($salesRoute);
        $this->assertSame('/second-admin/reports/sales', $salesRoute->getPath());

        $inventoryRoute = $router->getRouteCollection()->get('second_admin_inventory_report');
        $this->assertNotNull($inventoryRoute);
        $this->assertSame('/second-admin/reports/inventory', $inventoryRoute->getPath());
    }

    public function testStandaloneMethodRoutes(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // Standalone methods should create routes for all dashboards
        $action1Route = $router->getRouteCollection()->get('admin_standalone_action1');
        $this->assertNotNull($action1Route);
        $this->assertSame('/admin/standalone/action1', $action1Route->getPath());

        $action2Route = $router->getRouteCollection()->get('admin_standalone_action2');
        $this->assertNotNull($action2Route);
        $this->assertSame('/admin/standalone/action2', $action2Route->getPath());
        $this->assertContains('POST', $action2Route->getMethods());

        // Should also exist for second dashboard
        $action1Route2 = $router->getRouteCollection()->get('second_admin_standalone_action1');
        $this->assertNotNull($action1Route2);
        $this->assertSame('/second-admin/standalone/action1', $action1Route2->getPath());

        // #[AdminRoute] applied only to the method should not create a route for the class
        $this->assertNull($router->getRouteCollection()->get('admin_standalone'));
    }

    public function testStandaloneMethodCrudRoutes(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // Standalone CRUD methods should create routes for all dashboards
        $action1Route = $router->getRouteCollection()->get('admin_standalone_methods_crud_action1');
        $this->assertNotNull($action1Route);
        $this->assertSame('/admin/standalone-methods/crud/action1', $action1Route->getPath());

        $action2Route = $router->getRouteCollection()->get('admin_standalone_methods_crud_action2');
        $this->assertNotNull($action2Route);
        $this->assertSame('/admin/standalone-methods/crud/action2', $action2Route->getPath());
        $this->assertContains('POST', $action2Route->getMethods());

        // Should also exist for second dashboard
        $action1Route2 = $router->getRouteCollection()->get('second_admin_standalone_methods_crud_action1');
        $this->assertNotNull($action1Route2);
        $this->assertSame('/second-admin/standalone-methods/crud/action1', $action1Route2->getPath());

        // #[AdminRoute] applied only to the method should not create a route for the class
        $this->assertNull($router->getRouteCollection()->get('admin_standalone_methods'));
    }

    public function testRouteAccessibility(): void
    {
        $client = static::createClient();
        $client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'admin']);

        // Test invokable controller
        $client->request('GET', '/admin/custom-invokable');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Invokable Controller Response', $client->getResponse()->getContent());

        // Test foo list
        $client->request('GET', '/admin/foo/list');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Foo List', $client->getResponse()->getContent());

        // Test standalone action
        $client->request('GET', '/admin/standalone/action1');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Standalone Action 1', $client->getResponse()->getContent());

        // Test reports (should work on second dashboard)
        $client->request('GET', '/second-admin/reports/sales');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Sales Report', $client->getResponse()->getContent());
    }

    public function testRouteNamesAreCorrectlyGenerated(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');
        $routes = $router->getRouteCollection();

        // Collect all AdminRoute-generated routes
        $adminRoutes = [];
        foreach ($routes as $name => $route) {
            if ($route->hasDefault(EA::ROUTE_CREATED_BY_EASYADMIN)) {
                $adminRoutes[$name] = $route;
            }
        }

        // Check expected route names exist
        $expectedRoutes = [
            // Invokable controller routes
            'admin_custom_invokable',
            'second_admin_custom_invokable',

            // Foo routes (only for main dashboard except public_export)
            'admin_foo_list',
            'admin_foo_export_csv',
            'admin_foo_public_export',
            'second_admin_foo_public_export',

            // Reports routes (only for second dashboard)
            'second_admin_sales_report',
            'second_admin_inventory_report',

            // Standalone routes (for all dashboards)
            'admin_standalone_action1',
            'admin_standalone_action2',
            'second_admin_standalone_action1',
            'second_admin_standalone_action2',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertArrayHasKey($routeName, $adminRoutes, "Expected route '$routeName' not found");
        }
    }

    public function testRepeatedAdminRouteAttributes(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // test that repeated AdminRoute attributes on the same method generate multiple routes
        $route1 = $router->getRouteCollection()->get('admin_route1');
        $this->assertNotNull($route1, 'admin_route1 route should exist');
        $this->assertSame('/admin/route1/{id}', $route1->getPath());
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\RepeatedRouteController::twoRoutes',
            $route1->getDefault('_controller')
        );

        $route2 = $router->getRouteCollection()->get('admin_route2');
        $this->assertNotNull($route2, 'admin_route2 route should exist');
        $this->assertSame('/admin/route2/{id}', $route2->getPath());
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\RepeatedRouteController::twoRoutes',
            $route2->getDefault('_controller')
        );

        // test multiple routes pointing to the same action
        $route1 = $router->getRouteCollection()->get('admin_multiple_route1');
        $this->assertNotNull($route1, 'Multiple route 1 should exist');
        $this->assertSame('/admin/multiple/route1', $route1->getPath());

        $route2 = $router->getRouteCollection()->get('admin_multiple_route2');
        $this->assertNotNull($route2, 'Multiple route 2 should exist');
        $this->assertSame('/admin/multiple/route2', $route2->getPath());

        $route3 = $router->getRouteCollection()->get('admin_multiple_route3');
        $this->assertNotNull($route3, 'Multiple route 3 should exist');
        $this->assertSame('/admin/multiple/route3', $route3->getPath());

        // all three routes should point to the same controller action
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\RepeatedRouteController::multipleRoutes',
            $route1->getDefault('_controller')
        );
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\RepeatedRouteController::multipleRoutes',
            $route2->getDefault('_controller')
        );
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\RepeatedRouteController::multipleRoutes',
            $route3->getDefault('_controller')
        );

        // test that routes work for second dashboard too
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_route1'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_route2'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_multiple_route1'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_multiple_route2'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_multiple_route3'));
    }

    public function testMethodRoutesWithSameName(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // test that custom routes with same name in different CRUD controllers are generated for both dashboards
        $this->assertNotNull($router->getRouteCollection()->get('admin_same_action_one_same_action_name'));
        $this->assertNotNull($router->getRouteCollection()->get('admin_same_action_two_same_action_name'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_same_action_one_same_action_name'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_same_action_two_same_action_name'));
    }

    public function testRepeatedRoutesAreAccessible(): void
    {
        $client = static::createClient();
        $client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'admin']);

        $client->request('GET', '/admin/route1/123');
        $this->assertResponseIsSuccessful();
        $this->assertSame('ID: 123', $client->getResponse()->getContent());

        $client->request('GET', '/admin/route2/456');
        $this->assertResponseIsSuccessful();
        $this->assertSame('ID: 456', $client->getResponse()->getContent());

        $client->request('GET', '/admin/multiple/route1');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Multiple routes to same action', $client->getResponse()->getContent());

        $client->request('GET', '/admin/multiple/route2');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Multiple routes to same action', $client->getResponse()->getContent());

        $client->request('GET', '/admin/multiple/route3');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Multiple routes to same action', $client->getResponse()->getContent());
    }

    public function testClassLevelAdminRouteAsPrefix(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // test that class-level AdminRoute acts as a prefix when methods have AdminRoute
        $usersRoute = $router->getRouteCollection()->get('admin_api_users');
        $this->assertNotNull($usersRoute, 'API users route should exist');
        $this->assertSame('/admin/api/users', $usersRoute->getPath());
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\PrefixedController::listUsers',
            $usersRoute->getDefault('_controller')
        );

        $userDetailRoute = $router->getRouteCollection()->get('admin_api_user_detail');
        $this->assertNotNull($userDetailRoute, 'API user detail route should exist');
        $this->assertSame('/admin/api/users/{id}', $userDetailRoute->getPath());
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\PrefixedController::getUserDetail',
            $userDetailRoute->getDefault('_controller')
        );

        // test that routes work for second dashboard too
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_api_users'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_api_user_detail'));
    }

    public function testClassLevelRoutesAreAccessible(): void
    {
        $client = static::createClient();
        $client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'admin']);

        // Test prefixed routes
        $client->request('GET', '/admin/api/users');
        $this->assertResponseIsSuccessful();
        $this->assertSame('User list', $client->getResponse()->getContent());

        $client->request('GET', '/admin/api/users/789');
        $this->assertResponseIsSuccessful();
        $this->assertSame('User detail: 789', $client->getResponse()->getContent());
    }

    public function testMultipleAdminRoutesOnSameCrudAction(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // Test that multiple AdminRoute attributes on the same CRUD action generate multiple routes

        // customAction1 with two routes
        $action1Route = $router->getRouteCollection()->get('admin_multiple_route_action1');
        $this->assertNotNull($action1Route, 'Action1 route should exist');
        $this->assertSame('/admin/multiple-route/action1', $action1Route->getPath());
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\MultipleRouteCrudController::customAction1',
            $action1Route->getDefault('_controller')
        );

        $action1AltRoute = $router->getRouteCollection()->get('admin_multiple_route_action1_alt');
        $this->assertNotNull($action1AltRoute, 'Action1 alt route should exist');
        $this->assertSame('/admin/multiple-route/action1-alt', $action1AltRoute->getPath());
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\MultipleRouteCrudController::customAction1',
            $action1AltRoute->getDefault('_controller')
        );

        // customAction2 with three routes and different HTTP methods
        $action2Path1Route = $router->getRouteCollection()->get('admin_multiple_route_action2_path1');
        $this->assertNotNull($action2Path1Route, 'Action2 path1 route should exist');
        $this->assertSame('/admin/multiple-route/action2/path1', $action2Path1Route->getPath());
        $this->assertEquals(['GET'], $action2Path1Route->getMethods());
        $this->assertSame(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller\MultipleRouteCrudController::customAction2',
            $action2Path1Route->getDefault('_controller')
        );

        $action2Path2Route = $router->getRouteCollection()->get('admin_multiple_route_action2_path2');
        $this->assertNotNull($action2Path2Route, 'Action2 path2 route should exist');
        $this->assertSame('/admin/multiple-route/action2/path2', $action2Path2Route->getPath());
        $this->assertEquals(['GET'], $action2Path2Route->getMethods());

        $action2Path3Route = $router->getRouteCollection()->get('admin_multiple_route_action2_path3');
        $this->assertNotNull($action2Path3Route, 'Action2 path3 route should exist');
        $this->assertSame('/admin/multiple-route/action2/path3', $action2Path3Route->getPath());
        $this->assertContains('GET', $action2Path3Route->getMethods());
        $this->assertContains('POST', $action2Path3Route->getMethods());

        // customAction3 with entity ID parameter
        $action3Route = $router->getRouteCollection()->get('admin_multiple_route_action3');
        $this->assertNotNull($action3Route, 'Action3 route should exist');
        $this->assertSame('/admin/multiple-route/action3/{entityId}', $action3Route->getPath());

        $action3AltRoute = $router->getRouteCollection()->get('admin_multiple_route_action3_alt');
        $this->assertNotNull($action3AltRoute, 'Action3 alt route should exist');
        $this->assertSame('/admin/multiple-route/action3-alt/{entityId}', $action3AltRoute->getPath());

        // customAction4, where one of the routes doesn't define its path, only its name
        $action4Route = $router->getRouteCollection()->get('admin_multiple_route_action4');
        $this->assertNotNull($action4Route, 'Action4 route should exist');
        $this->assertSame('/admin/multiple-route/action4/{entityId}', $action4Route->getPath());

        $action4AltRoute = $router->getRouteCollection()->get('admin_multiple_route_custom_action4');
        $this->assertNotNull($action4AltRoute, 'Action4 alt route should exist with an autogenerated route name based on the method name');
        $this->assertSame('/admin/multiple-route/action4-alt/{entityId}', $action4AltRoute->getPath());

        // customAction5, where one of the routes doesn't define its name, only its path
        $action5Route = $router->getRouteCollection()->get('admin_multiple_route_action5');
        $this->assertNotNull($action5Route, 'Action5 route should exist');
        $this->assertSame('/admin/multiple-route/action5', $action5Route->getPath());

        $action5AltRoute = $router->getRouteCollection()->get('admin_multiple_route_action5_alt');
        $this->assertNotNull($action5AltRoute, 'Action5 alt route should exist with an autogenerated route path based on the method name');
        $this->assertSame('/admin/multiple-route/custom-action5', $action5AltRoute->getPath());

        // customAction6, where one of the routes doesn't define neither its name nor path
        $action6Route = $router->getRouteCollection()->get('admin_multiple_route_action6');
        $this->assertNotNull($action6Route, 'Action6 route should exist');
        $this->assertSame('/admin/multiple-route/action6', $action6Route->getPath());

        $action6AltRoute = $router->getRouteCollection()->get('admin_multiple_route_custom_action6');
        $this->assertNotNull($action6AltRoute, 'Action6 alt route should exist with an autogenerated route name and path based on the method name');
        $this->assertSame('/admin/multiple-route/custom-action6', $action6AltRoute->getPath());

        // Test that routes work for second dashboard too
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_multiple_route_action1'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_multiple_route_action1_alt'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_multiple_route_action2_path1'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_multiple_route_action2_path2'));
        $this->assertNotNull($router->getRouteCollection()->get('second_admin_multiple_route_action2_path3'));
    }
}
