<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\CacheKey;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Unified registry for Dashboard and CRUD controllers.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminControllerRegistry implements AdminControllerRegistryInterface
{
    /** @var array<string, string>|null */
    private ?array $controllerFqcnToRouteMap = null;
    /** @var array<string, string>|null */
    private ?array $routeToControllerFqcnMap = null;

    /** @var array<class-string, class-string>|null */
    private ?array $crudFqcnToEntityFqcnMap = null;
    /** @var array<class-string, class-string>|null */
    private ?array $entityFqcnToCrudFqcnMap = null;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
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
        return \count($this->getControllerFqcnToRouteMap());
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
        return array_keys($this->getControllerFqcnToRouteMap());
    }

    public function findCrudControllerByEntity(string $entityFqcn): ?string
    {
        if (null === $this->entityFqcnToCrudFqcnMap) {
            $this->loadCrudEntityCache();
        }

        return $this->entityFqcnToCrudFqcnMap[$entityFqcn] ?? null;
    }

    public function findEntityByCrudController(string $crudControllerFqcn): ?string
    {
        if (null === $this->crudFqcnToEntityFqcnMap) {
            $this->loadCrudEntityCache();
        }

        return $this->crudFqcnToEntityFqcnMap[$crudControllerFqcn] ?? null;
    }

    public function getAllCrudControllers(): array
    {
        if (null === $this->crudFqcnToEntityFqcnMap) {
            $this->loadCrudEntityCache();
        }

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
        $this->controllerFqcnToRouteMap = $this->cache->getItem(CacheKey::DASHBOARD_FQCN_TO_ROUTE)->get() ?? [];
        $this->routeToControllerFqcnMap = array_flip($this->controllerFqcnToRouteMap);
    }

    private function loadCrudEntityCache(): void
    {
        $this->crudFqcnToEntityFqcnMap = $this->cache->getItem(CacheKey::CRUD_FQCN_TO_ENTITY_FQCN)->get() ?? [];
        $this->entityFqcnToCrudFqcnMap = array_flip($this->crudFqcnToEntityFqcnMap);
    }
}
