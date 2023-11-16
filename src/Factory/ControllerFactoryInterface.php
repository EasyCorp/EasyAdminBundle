<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Lukas Lücke <lukas@luecke.me>
 */
interface ControllerFactoryInterface
{
    public function getDashboardControllerInstance(
        string $controllerFqcn,
        Request $request
    ): ?DashboardControllerInterface;

    public function getCrudControllerInstance(
        ?string $crudControllerFqcn,
        ?string $crudAction,
        Request $request
    ): ?CrudControllerInterface;
}
