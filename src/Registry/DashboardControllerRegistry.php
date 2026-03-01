<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use function Symfony\Component\String\u;

/**
 * @deprecated since 4.28.1, use AdminControllerRegistry instead
 */
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

    /**
     * @deprecated since 4.28.1, this concept (contextId) no longer exists in modern EasyAdmin
     */
    public function getControllerFqcnByContextId(string $contextId): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated and will be removed in EasyAdmin 5.x. The "contextId" concept no longer exists in modern EasyAdmin with pretty URLs.',
            __METHOD__
        );

        return $this->contextIdToControllerFqcnMap[$contextId] ?? null;
    }

    /**
     * @deprecated since 4.28.1, this concept (contextId) no longer exists in modern EasyAdmin
     */
    public function getContextIdByControllerFqcn(string $controllerFqcn): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated and will be removed in EasyAdmin 5.x. The "contextId" concept no longer exists in modern EasyAdmin with pretty URLs.',
            __METHOD__
        );

        return $this->controllerFqcnToContextIdMap[$controllerFqcn] ?? null;
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getDashboardByRoute() instead
     */
    public function getControllerFqcnByRoute(string $routeName): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::getDashboardByRoute()" instead.',
            __METHOD__
        );

        return $this->getRouteToControllerFqcnMap()[$routeName] ?? null;
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getDashboardRoute() instead
     */
    public function getRouteByControllerFqcn(string $controllerFqcn): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::getDashboardRoute()" instead.',
            __METHOD__
        );

        return $this->getControllerFqcnToRouteMap()[$controllerFqcn] ?? null;
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getDashboardCount() instead
     */
    public function getNumberOfDashboards(): int
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::getDashboardCount()" instead.',
            __METHOD__
        );

        return \count($this->controllerFqcnToContextIdMap);
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getFirstDashboardRoute() instead
     */
    public function getFirstDashboardRoute(): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::getFirstDashboardRoute()" instead.',
            __METHOD__
        );

        $map = $this->getControllerFqcnToRouteMap();

        return \count($map) < 1 ? null : $map[array_key_first($map)];
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getFirstDashboard() instead
     */
    public function getFirstDashboardFqcn(): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::getFirstDashboard()" instead.',
            __METHOD__
        );

        $map = $this->getControllerFqcnToRouteMap();

        return \count($map) < 1 ? null : array_key_first($map);
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getAllDashboards() instead
     */
    public function getAll(): array
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::getAllDashboards()" instead. Note: the return format is different (the new method returns a simple array of dashboard FQCNs).',
            __METHOD__
        );

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
