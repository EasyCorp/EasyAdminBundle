<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminUrlGeneratorInterface
{
    public function setDashboard(
        string $dashboardControllerFqcn
    ): AdminUrlGeneratorInterface;

    public function setController(
        string $crudControllerFqcn
    ): AdminUrlGeneratorInterface;

    public function setAction(string $action): AdminUrlGeneratorInterface;

    public function setRoute(
        string $routeName,
        array $routeParameters = []
    ): AdminUrlGeneratorInterface;

    public function setEntityId($entityId): AdminUrlGeneratorInterface;

    public function get(string $paramName);

    public function set(string $paramName, $paramValue): AdminUrlGeneratorInterface;

    public function setAll(array $routeParameters): AdminUrlGeneratorInterface;

    public function unset(string $paramName): AdminUrlGeneratorInterface;

    public function unsetAll(): AdminUrlGeneratorInterface;

    public function unsetAllExcept(
        string ...$namesOfParamsToKeep
    ): AdminUrlGeneratorInterface;

    public function includeReferrer(): AdminUrlGeneratorInterface;

    public function removeReferrer(): AdminUrlGeneratorInterface;

    public function setReferrer(string $referrer): AdminUrlGeneratorInterface;

    public function addSignature(bool $addSignature = true): AdminUrlGeneratorInterface;

    public function getSignature(): string;

    public function generateUrl(): string;
}
