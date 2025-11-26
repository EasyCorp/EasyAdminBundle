<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Cache;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CacheWarmerTest extends TestCase
{
    private string $cacheDirectory;
    private string $dashboardRoutesCacheFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = sprintf('%s/cache_dir_%d/', sys_get_temp_dir(), random_int(1, 999999));
        $this->dashboardRoutesCacheFile = $this->cacheDirectory.CacheWarmer::DASHBOARD_ROUTES_CACHE;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove($this->cacheDirectory);
    }

    public function testWarmUpWithNoRoutes()
    {
        $router = $this->getMockBuilder(RouterInterface::class)->getMock();
        $router->method('getRouteCollection')->willReturn(Kernel::MAJOR_VERSION >= 7 ? new RouteCollection() : []);

        $cacheWarmer = new CacheWarmer($router);
        $cacheWarmer->warmUp($this->cacheDirectory);

        $this->assertCachedRoutesEqual([]);
    }

    public function testWarmUp()
    {
        $routesDefinition = [
            'admin1' => new Route('/admin1', ['_controller' => TestingDashboardController::class.'::index']),
            'admin2' => new Route('/admin2', ['_controller' => TestingDashboardController::class]),
            'admin3' => new Route('/admin3', ['_controller' => [TestingDashboardController::class, 'index']]),
            'admin4' => new Route('/admin4', ['_controller' => [TestingDashboardController::class]]),
            'admin5' => new Route('/admin5', ['_controller' => TestingDashboardController::class.'::someMethod']),
            'admin6' => new Route('/admin6', ['_controller' => [TestingDashboardController::class, 'someMethod']]),
        ];

        $routeCollection = $routesDefinition;
        if (Kernel::MAJOR_VERSION >= 7) {
            $routeCollection = new RouteCollection();
            foreach ($routesDefinition as $name => $route) {
                $routeCollection->add($name, $route);
            }
        }

        $router = $this->getMockBuilder(RouterInterface::class)->getMock();
        $router->method('getRouteCollection')->willReturn($routeCollection);

        $cacheWarmer = new CacheWarmer($router);
        $cacheWarmer->warmUp($this->cacheDirectory);

        $this->assertCachedRoutesEqual([
            'admin1' => TestingDashboardController::class.'::index',
            'admin2' => TestingDashboardController::class.'::__invoke',
            'admin3' => TestingDashboardController::class.'::index',
            'admin4' => TestingDashboardController::class.'::__invoke',
        ]);
    }

    private function assertCachedRoutesEqual(array $expectedCachedRoutes)
    {
        $this->assertStringEqualsFile($this->dashboardRoutesCacheFile, '<?php return '.var_export($expectedCachedRoutes, true).';');
    }
}

final class TestingDashboardController extends AbstractDashboardController
{
}
