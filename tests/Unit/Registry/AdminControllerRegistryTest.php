<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Registry\AdminControllerRegistry;
use PHPUnit\Framework\TestCase;

class AdminControllerRegistryTest extends TestCase
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

    public function testGetDashboardRouteReturnsRouteWhenExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getDashboardRoute('App\Controller\Admin\DashboardController');

        $this->assertSame('admin_dashboard', $result);
    }

    public function testGetDashboardRouteReturnsNullWhenNotExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getDashboardRoute('App\Controller\NonExistentController');

        $this->assertNull($result);
    }

    public function testGetDashboardByRouteReturnsControllerWhenExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
            'reports_dashboard' => 'App\Controller\Reports\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getDashboardByRoute('admin_dashboard');

        $this->assertSame('App\Controller\Admin\DashboardController', $result);
    }

    public function testGetDashboardByRouteReturnsNullWhenNotExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getDashboardByRoute('nonexistent_route');

        $this->assertNull($result);
    }

    public function testGetDashboardCountReturnsCorrectCount(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->getDashboardCount();

        $this->assertSame(2, $result);
    }

    public function testGetDashboardCountReturnsZeroWhenEmpty(): void
    {
        $registry = new AdminControllerRegistry($this->tempDir, [], []);

        $result = $registry->getDashboardCount();

        $this->assertSame(0, $result);
    }

    public function testGetFirstDashboardReturnsFirstControllerWhenExists(): void
    {
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
            'reports_dashboard' => 'App\Controller\Reports\DashboardController::index',
        ]);

        $registry = $this->createRegistry();

        $result = $registry->getFirstDashboard();

        $this->assertSame('App\Controller\Admin\DashboardController', $result);
    }

    public function testGetFirstDashboardReturnsNullWhenNoDashboards(): void
    {
        $this->createCacheFile([]);

        $registry = new AdminControllerRegistry($this->tempDir, [], []);

        $result = $registry->getFirstDashboard();

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

        $registry = new AdminControllerRegistry($this->tempDir, [], []);

        $result = $registry->getFirstDashboardRoute();

        $this->assertNull($result);
    }

    public function testGetAllDashboardsReturnsAllDashboardControllers(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->getAllDashboards();

        $this->assertCount(2, $result);
        $this->assertContains('App\Controller\Admin\DashboardController', $result);
        $this->assertContains('App\Controller\Reports\DashboardController', $result);
    }

    public function testGetAllDashboardsReturnsEmptyArrayWhenNoDashboards(): void
    {
        $registry = new AdminControllerRegistry($this->tempDir, [], []);

        $result = $registry->getAllDashboards();

        $this->assertSame([], $result);
    }

    public function testFindCrudControllerByEntityReturnsControllerWhenExists(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->findCrudControllerByEntity('App\Entity\Product');

        $this->assertSame('App\Controller\ProductCrudController', $result);
    }

    public function testFindCrudControllerByEntityReturnsNullWhenNotExists(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->findCrudControllerByEntity('App\Entity\NonExistent');

        $this->assertNull($result);
    }

    public function testFindEntityByCrudControllerReturnsEntityWhenExists(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->findEntityByCrudController('App\Controller\CategoryCrudController');

        $this->assertSame('App\Entity\Category', $result);
    }

    public function testFindEntityByCrudControllerReturnsNullWhenNotExists(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->findEntityByCrudController('App\Controller\NonExistentCrudController');

        $this->assertNull($result);
    }

    public function testGetAllCrudControllersReturnsAllControllers(): void
    {
        $registry = $this->createRegistry();

        $result = $registry->getAllCrudControllers();

        $this->assertCount(3, $result);
        $this->assertContains('App\Controller\ProductCrudController', $result);
        $this->assertContains('App\Controller\CategoryCrudController', $result);
        $this->assertContains('App\Controller\UserCrudController', $result);
    }

    public function testGetAllCrudControllersReturnsEmptyArrayWhenNoControllers(): void
    {
        $registry = new AdminControllerRegistry($this->tempDir, [], []);

        $result = $registry->getAllCrudControllers();

        $this->assertSame([], $result);
    }

    public function testBidirectionalEntityCrudMapping(): void
    {
        $registry = $this->createRegistry();

        $entityFqcn = 'App\Entity\Category';
        $crudFqcn = $registry->findCrudControllerByEntity($entityFqcn);

        $this->assertNotNull($crudFqcn);

        $resolvedEntityFqcn = $registry->findEntityByCrudController($crudFqcn);
        $this->assertSame($entityFqcn, $resolvedEntityFqcn);
    }

    public function testRouteMapsAreLazilyLoaded(): void
    {
        // create registry before cache file exists
        $registry = $this->createRegistry();

        // now create the cache file
        $this->createCacheFile([
            'admin_dashboard' => 'App\Controller\Admin\DashboardController::index',
        ]);

        // the route should be found because maps are loaded lazily
        $result = $registry->getDashboardRoute('App\Controller\Admin\DashboardController');

        $this->assertSame('admin_dashboard', $result);
    }

    public function testRouteMapsHandleMissingCacheFile(): void
    {
        // don't create cache file
        $registry = $this->createRegistry();

        $result = $registry->getDashboardRoute('App\Controller\Admin\DashboardController');

        $this->assertNull($result);
    }

    public function testEmptyRegistry(): void
    {
        $emptyRegistry = new AdminControllerRegistry($this->tempDir, [], []);

        $this->assertSame(0, $emptyRegistry->getDashboardCount());
        $this->assertNull($emptyRegistry->getFirstDashboard());
        $this->assertNull($emptyRegistry->getFirstDashboardRoute());
        $this->assertSame([], $emptyRegistry->getAllDashboards());
        $this->assertSame([], $emptyRegistry->getAllCrudControllers());
        $this->assertNull($emptyRegistry->findCrudControllerByEntity('App\Entity\Product'));
        $this->assertNull($emptyRegistry->findEntityByCrudController('App\Controller\ProductCrudController'));
    }

    private function createRegistry(): AdminControllerRegistry
    {
        $crudFqcnToEntityFqcnMap = [
            'App\Controller\ProductCrudController' => 'App\Entity\Product',
            'App\Controller\CategoryCrudController' => 'App\Entity\Category',
            'App\Controller\UserCrudController' => 'App\Entity\User',
        ];

        $dashboardControllers = [
            'App\Controller\Admin\DashboardController',
            'App\Controller\Reports\DashboardController',
        ];

        return new AdminControllerRegistry(
            $this->tempDir,
            $crudFqcnToEntityFqcnMap,
            $dashboardControllers
        );
    }

    private function createCacheFile(array $routes): void
    {
        $content = '<?php return '.var_export($routes, true).';';
        file_put_contents($this->tempDir.'/easyadmin/routes-dashboard.php', $content);
    }
}
