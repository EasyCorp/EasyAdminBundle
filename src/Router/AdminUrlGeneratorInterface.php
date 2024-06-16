<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminUrlGeneratorInterface
{
    public function setDashboard(string $dashboardControllerFqcn): self;

    public function setController(string $crudControllerFqcn): self;

    public function setAction(string $action): self;

    public function setRoute(
        string $routeName,
        array $routeParameters = []
    ): self;

    public function setEntityId($entityId): self;

    public function get(string $paramName): mixed;

    public function set(string $paramName, $paramValue): self;

    public function setAll(array $routeParameters): self;

    public function unset(string $paramName): self;

    public function unsetAll(): self;

    public function unsetAllExcept(string ...$namesOfParamsToKeep): self;

    public function includeReferrer(): self;

    public function removeReferrer(): self;

    public function setReferrer(string $referrer): self;

    public function addSignature(bool $addSignature = true): self;

    public function getSignature(): string;

    public function generateUrl(): string;
}
