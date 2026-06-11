<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

/**
 * @deprecated since 4.28.1, use AdminControllerRegistryInterface instead
 */
interface DashboardControllerRegistryInterface
{
    /**
     * @deprecated since 4.28.1, this concept (contextId) no longer exists in modern EasyAdmin
     */
    public function getControllerFqcnByContextId(string $contextId): ?string;

    /**
     * @deprecated since 4.28.1, this concept (contextId) no longer exists in modern EasyAdmin
     */
    public function getContextIdByControllerFqcn(string $controllerFqcn): ?string;

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getDashboardByRoute() instead
     */
    public function getControllerFqcnByRoute(string $routeName): ?string;

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getDashboardRoute() instead
     */
    public function getRouteByControllerFqcn(string $controllerFqcn): ?string;

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getDashboardCount() instead
     */
    public function getNumberOfDashboards(): int;

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getFirstDashboardRoute() instead
     */
    public function getFirstDashboardRoute(): ?string;

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getFirstDashboard() instead
     */
    public function getFirstDashboardFqcn(): ?string;

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getAllDashboards() instead
     *
     * @return array<int, array{controller: string, route: string, context: string}>
     */
    public function getAll(): array;
}
