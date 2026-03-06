<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminUrlGeneratorInterface
{
    public function setDashboard(string $dashboardControllerFqcn): self;

    public function setController(string $crudControllerFqcn): self;

    public function setAction(string $action): self;

    /**
     * @param array<string, mixed> $routeParameters
     */
    public function setRoute(
        string $routeName,
        array $routeParameters = [],
    ): self;

    public function setEntityId(mixed $entityId): self;

    public function get(string $paramName): mixed;

    public function set(string $paramName, mixed $paramValue): self;

    /**
     * @param array<string, mixed> $routeParameters
     */
    public function setAll(array $routeParameters): self;

    public function unset(string $paramName): self;

    public function unsetAll(): self;

    public function unsetAllExcept(string ...$namesOfParamsToKeep): self;

    public function generateUrl(): string;
}
