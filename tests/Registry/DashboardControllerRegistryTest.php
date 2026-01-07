<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use PHPUnit\Framework\TestCase;

class DashboardControllerRegistryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/easyadmin_test_'.uniqid(more_entropy: true);
        mkdir($this->tempDir.'/easyadmin', 0777, true);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir.'/easyadmin/routes-dashboard.php')) {
            unlink($this->tempDir.'/easyadmin/routes-dashboard.php');
        }
        if (is_dir($this->tempDir.'/easyadmin')) {
            rmdir($this->tempDir.'/easyadmin');
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testGetControllerFqcnByContextIdReturnsControllerWhenExists(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->getControllerFqcnByContextId('admin');

        $this->assertSame('App\Controller\Admin\DashboardController', $result);
    }

    public function testGetControllerFqcnByContextIdReturnsNullWhenNotExists(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->getControllerFqcnByContextId('nonexistent');

        $this->assertNull($result);
    }

    public function testGetContextIdByControllerFqcnReturnsContextWhenExists(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->getContextIdByControllerFqcn('App\Controller\Admin\DashboardController');

        $this->assertSame('admin', $result);
    }

    public function testGetContextIdByControllerFqcnReturnsNullWhenNotExists(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->getContextIdByControllerFqcn('App\Controller\NonExistentController');

        $this->assertNull($result);
    }

    public function testGetNumberOfDashboardsReturnsCorrectCount(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->getNumberOfDashboards();

        $this->assertSame(2, $result);
    }

    public function testGetNumberOfDashboardsReturnsZeroWhenEmpty(): void
    {
        $registry = new DashboardControllerRegistry($this->tempDir, [], []);

        $result = $registry->getNumberOfDashboards();

        $this->assertSame(0, $result);
    }

    public function testGetControllerFqcnByRouteReturnsControllerWhenRouteExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
            'reports_dashboard' => 'App\Controller\Reports\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getControllerFqcnByRoute('admin_dashboard');

        $this->assertSame('App\Controller\Admin\DashboardController', $result);
    }

    public function testGetControllerFqcnByRouteReturnsNullWhenRouteNotExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getControllerFqcnByRoute('nonexistent_route');

        $this->assertNull($result);
    }

    public function testGetRouteByControllerFqcnReturnsRouteWhenControllerExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getRouteByControllerFqcn('App\Controller\Admin\DashboardController');

        $this->assertSame('admin_dashboard', $result);
    }

    public function testGetRouteByControllerFqcnReturnsNullWhenControllerNotExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getRouteByControllerFqcn('App\Controller\NonExistentController');

        $this->assertNull($result);
    }

    public function testGetFirstDashboardRouteReturnsFirstRouteWhenExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
            'reports_dashboard' => 'App\Controller\Reports\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getFirstDashboardRoute();

        $this->assertSame('admin_dashboard', $result);
    }

    public function testGetFirstDashboardRouteReturnsNullWhenNoDashboards(): void
    {
        $this->createCacheFile([]);

        $registry = new DashboardControllerRegistry($this->tempDir, [], []);

        $result = $registry->getFirstDashboardRoute();

        $this->assertNull($result);
    }

    public function testGetFirstDashboardFqcnReturnsFirstControllerWhenExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
            'reports_dashboard' => 'App\Controller\Reports\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getFirstDashboardFqcn();

        $this->assertSame('App\Controller\Admin\DashboardController', $result);
    }

    public function testGetFirstDashboardFqcnReturnsNullWhenNoDashboards(): void
    {
        $this->createCacheFile([]);

        $registry = new DashboardControllerRegistry($this->tempDir, [], []);

        $result = $registry->getFirstDashboardFqcn();

        $this->assertNull($result);
    }

    public function testGetAllReturnsAllDashboardsWithRoutes(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
            'reports_dashboard' => 'App\Controller\Reports\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        // trigger lazy loading of routes by calling getRouteByControllerFqcn first
        $registry->getRouteByControllerFqcn('App\Controller\Admin\DashboardController');

        $result = $registry->getAll();

        $this->assertCount(2, $result);
        $this->assertSame([
            [
                'controller' => 'App\Controller\Admin\DashboardController',
                'route' => 'admin_dashboard',
                'context' => 'admin',
            ],
            [
                'controller' => 'App\Controller\Reports\DashboardController',
                'route' => 'reports_dashboard',
                'context' => 'reports',
            ],
        ], $result);
    }

    public function testGetAllReturnsNullRoutesWhenCacheNotLoaded(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        // don't trigger lazy loading - routes will be null
        $result = $registry->getAll();

        $this->assertCount(2, $result);
        $this->assertNull($result[0]['route']);
        $this->assertNull($result[1]['route']);
    }

    public function testGetAllReturnsEmptyArrayWhenNoDashboards(): void
    {
        $registry = new DashboardControllerRegistry($this->tempDir, [], []);

        $result = $registry->getAll();

        $this->assertSame([], $result);
    }

    public function testRouteMapsAreLazilyLoaded(): void
    {
        // create cache file after registry is created
        $registry = $this->createRegistry();

        // now create the cache file
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        // the route should be found because maps are loaded lazily
        $result = $registry->getRouteByControllerFqcn('App\Controller\Admin\DashboardController');

        $this->assertSame('admin_dashboard', $result);
    }

    public function testRouteMapsHandleMissingCacheFile(): void
    {
        // don't create cache file
        $registry = $this->createRegistry();

        $result = $registry->getRouteByControllerFqcn('App\Controller\Admin\DashboardController');

        $this->assertNull($result);
    }

    private function createRegistry(): DashboardControllerRegistry
    {
        return new DashboardControllerRegistry(
            $this->tempDir,
            [
                'App\Controller\Admin\DashboardController' => 'admin',
                'App\Controller\Reports\DashboardController' => 'reports',
            ],
            [
                'admin' => 'App\Controller\Admin\DashboardController',
                'reports' => 'App\Controller\Reports\DashboardController',
            ]
        );
    }

    private function createCacheFile(array $routes): void
    {
        $content = '<?php return '.var_export($routes, true).';';
        file_put_contents($this->tempDir.'/easyadmin/routes-dashboard.php', $content);
    }
}
