<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\CacheKey;
use EasyCorp\Bundle\EasyAdminBundle\Registry\AdminControllerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class AdminControllerRegistryTest extends TestCase
{
    public function testGetDashboardRouteReturnsRouteWhenExists(): void
    {
        $registry = $this->createRegistry([
            'App\Controller\Admin\DashboardController' => 'admin_dashboard',
        ]);

        $result = $registry->getDashboardRoute('App\Controller\Admin\DashboardController');

        $this->assertSame('admin_dashboard', $result);
    }

    public function testGetDashboardRouteReturnsNullWhenNotExists(): void
    {
        $registry = $this->createRegistry([
            'App\Controller\Admin\DashboardController' => 'admin_dashboard',
        ]);

        $result = $registry->getDashboardRoute('App\Controller\NonExistentController');

        $this->assertNull($result);
    }

    public function testGetDashboardByRouteReturnsControllerWhenExists(): void
    {
        $registry = $this->createRegistry([
            'App\Controller\Admin\DashboardController' => 'admin_dashboard',
            'App\Controller\Reports\DashboardController' => 'reports_dashboard',
        ]);

        $result = $registry->getDashboardByRoute('admin_dashboard');

        $this->assertSame('App\Controller\Admin\DashboardController', $result);
    }

    public function testGetDashboardByRouteReturnsNullWhenNotExists(): void
    {
        $registry = $this->createRegistry([
            'App\Controller\Admin\DashboardController' => 'admin_dashboard',
        ]);

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
        $registry = $this->createRegistry([]);

        $result = $registry->getDashboardCount();

        $this->assertSame(0, $result);
    }

    public function testGetFirstDashboardReturnsFirstControllerWhenExists(): void
    {
        $registry = $this->createRegistry([
            'App\Controller\Admin\DashboardController' => 'admin_dashboard',
            'App\Controller\Reports\DashboardController' => 'reports_dashboard',
        ]);

        $result = $registry->getFirstDashboard();

        $this->assertSame('App\Controller\Admin\DashboardController', $result);
    }

    public function testGetFirstDashboardReturnsNullWhenNoDashboards(): void
    {
        $registry = $this->createRegistry([]);

        $result = $registry->getFirstDashboard();

        $this->assertNull($result);
    }

    public function testGetFirstDashboardRouteReturnsFirstRouteWhenExists(): void
    {
        $registry = $this->createRegistry([
            'App\Controller\Admin\DashboardController' => 'admin_dashboard',
            'App\Controller\Reports\DashboardController' => 'reports_dashboard',
        ]);

        $result = $registry->getFirstDashboardRoute();

        $this->assertSame('admin_dashboard', $result);
    }

    public function testGetFirstDashboardRouteReturnsNullWhenNoDashboards(): void
    {
        $registry = $this->createRegistry([]);

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
        $registry = $this->createRegistry([]);

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
        $registry = $this->createRegistry([], []);

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
        // create cache and populate it after creating the registry
        $cache = new ArrayAdapter();
        $registry = new AdminControllerRegistry($cache);

        // now populate the cache
        $item = $cache->getItem(CacheKey::DASHBOARD_FQCN_TO_ROUTE);
        $item->set([
            'App\Controller\Admin\DashboardController' => 'admin_dashboard',
        ]);
        $cache->save($item);

        // the route should be found because maps are loaded lazily
        $result = $registry->getDashboardRoute('App\Controller\Admin\DashboardController');

        $this->assertSame('admin_dashboard', $result);
    }

    public function testRouteMapsHandleEmptyCache(): void
    {
        // don't populate cache
        $registry = $this->createRegistry([]);

        $result = $registry->getDashboardRoute('App\Controller\Admin\DashboardController');

        $this->assertNull($result);
    }

    public function testEmptyRegistry(): void
    {
        $emptyRegistry = $this->createRegistry([], []);

        $this->assertSame(0, $emptyRegistry->getDashboardCount());
        $this->assertNull($emptyRegistry->getFirstDashboard());
        $this->assertNull($emptyRegistry->getFirstDashboardRoute());
        $this->assertSame([], $emptyRegistry->getAllDashboards());
        $this->assertSame([], $emptyRegistry->getAllCrudControllers());
        $this->assertNull($emptyRegistry->findCrudControllerByEntity('App\Entity\Product'));
        $this->assertNull($emptyRegistry->findEntityByCrudController('App\Controller\ProductCrudController'));
    }

    private function createRegistry(
        ?array $dashboardRoutes = null,
        ?array $crudToEntityMap = null,
    ): AdminControllerRegistry {
        $cache = new ArrayAdapter();

        if (null === $dashboardRoutes) {
            $dashboardRoutes = [
                'App\Controller\Admin\DashboardController' => 'admin_dashboard',
                'App\Controller\Reports\DashboardController' => 'reports_dashboard',
            ];
        }

        $item = $cache->getItem(CacheKey::DASHBOARD_FQCN_TO_ROUTE);
        $item->set($dashboardRoutes);
        $cache->save($item);

        if (null === $crudToEntityMap) {
            $crudToEntityMap = [
                'App\Controller\ProductCrudController' => 'App\Entity\Product',
                'App\Controller\CategoryCrudController' => 'App\Entity\Category',
                'App\Controller\UserCrudController' => 'App\Entity\User',
            ];
        }

        $item = $cache->getItem(CacheKey::CRUD_FQCN_TO_ENTITY_FQCN);
        $item->set($crudToEntityMap);
        $cache->save($item);

        return new AdminControllerRegistry($cache);
    }
}
