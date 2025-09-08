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
}
