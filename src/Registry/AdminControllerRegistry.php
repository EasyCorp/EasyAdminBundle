<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use function Symfony\Component\String\u;

/**
 * Unified registry for Dashboard and CRUD controllers.
 * Replaces the deprecated DashboardControllerRegistry and CrudControllerRegistry.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminControllerRegistry implements AdminControllerRegistryInterface
{
    /** @var array<string, string>|null */
    private ?array $controllerFqcnToRouteMap = null;
    /** @var array<string, string>|null */
    private ?array $routeToControllerFqcnMap = null;

    /** @var array<class-string, class-string> */
    private readonly array $entityFqcnToCrudFqcnMap;

    /**
     * @param array<class-string, class-string> $crudFqcnToEntityFqcnMap CRUD controller FQCN => Entity FQCN
     * @param array<class-string>               $dashboardControllers    List of Dashboard controller FQCNs
     */
    public function __construct(
        private readonly string $buildDir,
        private readonly array $crudFqcnToEntityFqcnMap,
        private readonly array $dashboardControllers,
    ) {
        $this->entityFqcnToCrudFqcnMap = array_flip($crudFqcnToEntityFqcnMap);
    }

    public function getDashboardRoute(string $dashboardFqcn): ?string
    {
        return $this->getControllerFqcnToRouteMap()[$dashboardFqcn] ?? null;
    }

    public function getDashboardByRoute(string $routeName): ?string
    {
        return $this->getRouteToControllerFqcnMap()[$routeName] ?? null;
    }

    public function getDashboardCount(): int
    {
        return \count($this->dashboardControllers);
    }

    public function getFirstDashboard(): ?string
    {
        $map = $this->getControllerFqcnToRouteMap();

        return \count($map) < 1 ? null : array_key_first($map);
    }

    public function getFirstDashboardRoute(): ?string
    {
        $map = $this->getControllerFqcnToRouteMap();

        return \count($map) < 1 ? null : $map[array_key_first($map)];
    }

    public function getAllDashboards(): array
    {
        return $this->dashboardControllers;
    }

    public function findCrudControllerByEntity(string $entityFqcn): ?string
    {
        return $this->entityFqcnToCrudFqcnMap[$entityFqcn] ?? null;
    }

    public function findEntityByCrudController(string $crudControllerFqcn): ?string
    {
        return $this->crudFqcnToEntityFqcnMap[$crudControllerFqcn] ?? null;
    }

    public function getAllCrudControllers(): array
    {
        return array_keys($this->crudFqcnToEntityFqcnMap);
    }

    /**
     * @return array<string, string>
     */
    private function getControllerFqcnToRouteMap(): array
    {
        if (null === $this->controllerFqcnToRouteMap) {
            $this->loadDashboardRoutesCache();
        }

        return $this->controllerFqcnToRouteMap;
    }

    /**
     * @return array<string, string>
     */
    private function getRouteToControllerFqcnMap(): array
    {
        if (null === $this->routeToControllerFqcnMap) {
            $this->loadDashboardRoutesCache();
        }

        return $this->routeToControllerFqcnMap;
    }

    private function loadDashboardRoutesCache(): void
    {
        $this->controllerFqcnToRouteMap = [];

        $dashboardRoutesCachePath = $this->buildDir.'/'.CacheWarmer::DASHBOARD_ROUTES_CACHE;
        $dashboardControllerRoutes = file_exists($dashboardRoutesCachePath) ? require $dashboardRoutesCachePath : [];

        foreach ($dashboardControllerRoutes as $routeName => $controller) {
            $this->controllerFqcnToRouteMap[u($controller)->before('::')->toString()] = $routeName;
        }

        $this->routeToControllerFqcnMap = array_flip($this->controllerFqcnToRouteMap);
    }
}
