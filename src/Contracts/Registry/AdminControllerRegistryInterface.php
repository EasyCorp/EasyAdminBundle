<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry;

/**
 * Unified registry for Dashboard and CRUD controllers.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminControllerRegistryInterface
{
    /**
     * Returns the route name for the given Dashboard controller FQCN.
     *
     * @param class-string $dashboardFqcn
     */
    public function getDashboardRoute(string $dashboardFqcn): ?string;

    /**
     * Returns the Dashboard controller FQCN for the given route name.
     *
     * @return class-string|null
     */
    public function getDashboardByRoute(string $routeName): ?string;

    /**
     * Returns the number of registered Dashboard controllers.
     */
    public function getDashboardCount(): int;

    /**
     * Returns the FQCN of the first registered Dashboard controller.
     *
     * @return class-string|null
     */
    public function getFirstDashboard(): ?string;

    /**
     * Returns the route name of the first registered Dashboard controller.
     */
    public function getFirstDashboardRoute(): ?string;

    /**
     * Returns all registered Dashboard controller FQCNs.
     *
     * @return array<class-string>
     */
    public function getAllDashboards(): array;

    /**
     * Returns the CRUD controller FQCN that manages the given entity.
     *
     * Note: If multiple CRUD controllers manage the same entity, only the
     * last one registered will be returned. Use explicit controller references
     * when multiple controllers manage the same entity.
     *
     * @param class-string $entityFqcn
     *
     * @return class-string|null
     */
    public function findCrudControllerByEntity(string $entityFqcn): ?string;

    /**
     * Returns the entity FQCN managed by the given CRUD controller.
     *
     * @param class-string $crudControllerFqcn
     *
     * @return class-string|null
     */
    public function findEntityByCrudController(string $crudControllerFqcn): ?string;

    /**
     * Returns all registered CRUD controller FQCNs.
     *
     * @return array<class-string>
     */
    public function getAllCrudControllers(): array;
}
