<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Lukas Lücke <lukas@luecke.me>
 */
final class ControllerFactory
{
    public function __construct(private readonly ControllerResolverInterface $controllerResolver)
    {
    }

    public function getDashboardControllerInstance(string $controllerFqcn, Request $request): ?DashboardControllerInterface
    {
        return $this->getDashboardController($controllerFqcn, $request);
    }

    public function getCrudControllerInstance(?string $crudControllerFqcn, ?string $crudAction, Request $request): ?CrudControllerInterface
    {
        if (null === $crudControllerFqcn) {
            return null;
        }

        return $this->getCrudController($crudControllerFqcn, $crudAction, $request);
    }

    private function getDashboardController(?string $dashboardControllerFqcn, Request $request): ?DashboardControllerInterface
    {
        return $this->getController(DashboardControllerInterface::class, $dashboardControllerFqcn, 'index', $request);
    }

    private function getCrudController(?string $crudControllerFqcn, ?string $crudAction, Request $request): ?CrudControllerInterface
    {
        return $this->getController(CrudControllerInterface::class, $crudControllerFqcn, $crudAction, $request);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $controllerInterface
     *
     * @return T|null
     */
    private function getController(string $controllerInterface, ?string $controllerFqcn, ?string $controllerAction, Request $request): ?object
    {
        if (null === $controllerFqcn || null === $controllerAction) {
            return null;
        }

        // needed to fix the double encoding of URLs that might happen (https://github.com/EasyCorp/EasyAdminBundle/pull/6902)
        $controllerFqcn = str_replace('%5C', '\\', $controllerFqcn);
        $newRequest = $request->duplicate(null, null, ['_controller' => [$controllerFqcn, $controllerAction]]);
        try {
            $controllerCallable = $this->controllerResolver->getController($newRequest);
        } catch (\InvalidArgumentException $e) {
            $controllerCallable = false;
        }

        if (false === $controllerCallable) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller "%s::%s".', $controllerFqcn, $controllerAction));
        }

        if (!\is_array($controllerCallable)) {
            return null;
        }

        $controllerInstance = $controllerCallable[0];
        if (!\is_object($controllerInstance)) {
            return null;
        }

        return is_subclass_of($controllerInstance, $controllerInterface) ? $controllerInstance : null;
    }
}
