<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class DashboardControllerRegistry
{
    private $controllerFqcnToContextIdMap = [];
    private $contextIdToControllerFqcnMap = [];
    private $controllerFqcnToRouteMap = [];
    private $routeToControllerFqcnMap = [];

    public function __construct(string $kernelSecret, string $cacheDir, array $dashboardControllersFqcn)
    {
        foreach ($dashboardControllersFqcn as $controllerFqcn) {
            $this->controllerFqcnToContextIdMap[$controllerFqcn] = substr(sha1($kernelSecret.$controllerFqcn), 0, 7);
        }

        $this->contextIdToControllerFqcnMap = array_flip($this->controllerFqcnToContextIdMap);

        $dashboardRoutesCachePath = $cacheDir.'/'.CacheWarmer::DASHBOARD_ROUTES_CACHE;
        $dashboardControllerRoutes = !file_exists($dashboardRoutesCachePath) ? [] : require $dashboardRoutesCachePath;
        foreach ($dashboardControllerRoutes as $routeName => $controller) {
            $this->controllerFqcnToRouteMap[u($controller)->before('::')->toString()] = $routeName;
        }

        $this->routeToControllerFqcnMap = array_flip($this->controllerFqcnToRouteMap);
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
        return $this->routeToControllerFqcnMap[$routeName] ?? null;
    }

    public function getRouteByControllerFqcn(string $controllerFqcn): ?string
    {
        return $this->controllerFqcnToRouteMap[$controllerFqcn] ?? null;
    }

    public function getNumberOfDashboards(): int
    {
        return \count($this->controllerFqcnToContextIdMap);
    }

    public function getFirstDashboardRoute(): ?string
    {
        return \count($this->controllerFqcnToRouteMap) < 1 ? null : $this->controllerFqcnToRouteMap[array_key_first($this->controllerFqcnToRouteMap)];
    }
}
