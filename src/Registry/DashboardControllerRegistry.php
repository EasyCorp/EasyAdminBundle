<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use function Symfony\Component\String\u;

final class DashboardControllerRegistry implements DashboardControllerRegistryInterface
{
    /** @var array<string, string>|null */
    private ?array $controllerFqcnToRouteMap = null;
    /** @var array<string, string>|null */
    private ?array $routeToControllerFqcnMap = null;

    /**
     * @param string[] $controllerFqcnToContextIdMap
     * @param string[] $contextIdToControllerFqcnMap
     */
    public function __construct(
        private readonly string $buildDir,
        private readonly array $controllerFqcnToContextIdMap,
        private readonly array $contextIdToControllerFqcnMap,
    ) {
    }

    public function getControllerFqcnByContextId(string $contextId): ?string
    {
        return $this->contextIdToControllerFqcnMap[$contextId] ?? null;
    }

    public function getContextIdByControllerFqcn(string $controllerFqcn): ?string
    {
        return $this->controllerFqcnToContextIdMap[$controllerFqcn] ?? null;
    }

    public function getControllerFqcnByRoute(string $routeName): ?string
    {
        return $this->getRouteToControllerFqcnMap()[$routeName] ?? null;
    }

    public function getRouteByControllerFqcn(string $controllerFqcn): ?string
    {
        return $this->getControllerFqcnToRouteMap()[$controllerFqcn] ?? null;
    }

    public function getNumberOfDashboards(): int
    {
        return \count($this->controllerFqcnToContextIdMap);
    }

    public function getFirstDashboardRoute(): ?string
    {
        $map = $this->getControllerFqcnToRouteMap();

        return \count($map) < 1 ? null : $map[array_key_first($map)];
    }

    public function getFirstDashboardFqcn(): ?string
    {
        $map = $this->getControllerFqcnToRouteMap();

        return \count($map) < 1 ? null : array_key_first($map);
    }

    public function getAll(): array
    {
        $dashboards = [];
        foreach ($this->controllerFqcnToContextIdMap as $controllerFqcn => $contextId) {
            $dashboards[] = [
                'controller' => $controllerFqcn,
                'route' => $this->controllerFqcnToRouteMap[$controllerFqcn] ?? null,
                'context' => $contextId,
            ];
        }

        return $dashboards;
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
