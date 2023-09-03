<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface DashboardControllerRegistryInterface
{
    public function getControllerFqcnByContextId(string $contextId): ?string;

    public function getContextIdByControllerFqcn(string $controllerFqcn): ?string;

    public function getControllerFqcnByRoute(string $routeName): ?string;

    public function getRouteByControllerFqcn(string $controllerFqcn): ?string;

    public function getNumberOfDashboards(): int;

    public function getFirstDashboardRoute(): ?string;
}
