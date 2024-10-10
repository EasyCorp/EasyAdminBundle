<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminRouteGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminRouteGenerator implements AdminRouteGeneratorInterface
{
    // the order in which routes are defined here is important because routes
    // are added to the application in the same order and e.g. the path of the
    // 'detail' route collides with the 'new' route and must be defined after it
    private const ROUTES = [
        'index' => [
            'path' => '/',
            'methods' => ['GET'],
        ],
        'new' => [
            'path' => '/new',
            'methods' => ['GET', 'POST'],
        ],
        'batchDelete' => [
            'path' => '/batchDelete',
            'methods' => ['POST'],
        ],
        'autocomplete' => [
            'path' => '/autocomplete',
            'methods' => ['GET'],
        ],
        'edit' => [
            'path' => '/{entityId}/edit',
            'methods' => ['GET', 'POST', 'PATCH'],
        ],
        'delete' => [
            'path' => '/{entityId}/delete',
            'methods' => ['POST'],
        ],
        'detail' => [
            'path' => '/{entityId}',
            'methods' => ['GET'],
        ],
    ];

    public function __construct(
        private iterable $dashboardControllers,
        private iterable $crudControllers,
    ) {
    }

    public function generateAll(): RouteCollection
    {
        $collection = new RouteCollection();
        $addedRouteNames = [];
        foreach ($this->dashboardControllers as $dashboardController) {
            $dashboardFqcn = \get_class($dashboardController);
            foreach ($this->crudControllers as $crudController) {
                $crudControllerFqcn = \get_class($crudController);

                foreach (self::ROUTES as $actionName => $actionConfig) {
                    $crudActionRouteName = $this->getRouteName($dashboardFqcn, $crudControllerFqcn, $actionName);
                    $crudActionPath = $this->getRoutePath($dashboardFqcn, $crudControllerFqcn, $actionName);

                    $defaults = [
                        '_controller' => $crudControllerFqcn.'::'.$actionName,
                    ];
                    $options = [
                        EA::ROUTE_CREATED_BY_EASYADMIN => true,
                        EA::DASHBOARD_CONTROLLER_FQCN => $dashboardFqcn,
                        EA::CRUD_CONTROLLER_FQCN => $crudControllerFqcn,
                        EA::CRUD_ACTION => $actionName,
                    ];

                    $route = new Route($crudActionPath, $defaults, [], $options, '', [], self::ROUTES[$actionName]['methods']);

                    if (\in_array($crudActionRouteName, $addedRouteNames, true)) {
                        throw new \RuntimeException(sprintf('When using pretty URLs, all CRUD controllers must have unique PHP class names to generate unique route names. However, your application has at least two controllers with the FQCN "%s", generating the route "%s". Even if both CRUD controllers are in different namespaces, they cannot have the same class name. Rename one of these controllers to resolve the issue.', $crudControllerFqcn, $crudActionRouteName));
                    }

                    $collection->add($crudActionRouteName, $route);
                    $addedRouteNames[] = $crudActionRouteName;
                }
            }
        }

        return $collection;
    }

    public function getRouteName(string $dashboardFqcn, string $crudControllerFqcn, string $action): ?string
    {
        // EasyAdmin routes are only available for built-in CRUD actions
        if (!\in_array($action, Crud::ACTION_NAMES, true)) {
            return null;
        }

        $dashboardRouteConfiguration = $this->getDashboardsRouteConfiguration();
        $dashboardRouteName = $dashboardRouteConfiguration[$dashboardFqcn]['route_name'];
        $crudControllerShortName = $this->getCrudControllerShortName($crudControllerFqcn);

        return sprintf('%s_%s_%s', $dashboardRouteName, $crudControllerShortName, $action);
    }

    public function getRoutePath(string $dashboardFqcn, string $crudControllerFqcn, string $action): ?string
    {
        // EasyAdmin routes are only available for built-in CRUD actions
        if (!\in_array($action, Crud::ACTION_NAMES, true)) {
            return null;
        }

        $dashboardRouteConfiguration = $this->getDashboardsRouteConfiguration();
        $dashboardRoutePath = $dashboardRouteConfiguration[$dashboardFqcn]['route_path'];
        $crudControllerShortName = $this->getCrudControllerShortName($crudControllerFqcn);

        return sprintf('%s/%s%s', $dashboardRoutePath, $crudControllerShortName, self::ROUTES[$action]['path']);
    }

    private function getCrudControllerShortName(string $crudControllerFqcn): string
    {
        // transforms 'App\Controller\Admin\FooBarBazCrudController' into 'foo_bar_baz'
        $shortName = str_replace(['CrudController', 'Controller'], '', (new \ReflectionClass($crudControllerFqcn))->getShortName());
        $shortName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));

        return $shortName;
    }

    private function getDashboardsRouteConfiguration(): array
    {
        $config = [];

        foreach ($this->dashboardControllers as $dashboardController) {
            $reflectionClass = new \ReflectionClass($dashboardController);
            $indexMethod = $reflectionClass->getMethod('index');
            $attributes = $indexMethod->getAttributes('Symfony\\Component\\Routing\\Attribute\\Route');
            if ([] === $attributes) {
                $attributes = $indexMethod->getAttributes('Symfony\\Component\\Routing\\Annotation\\Route');
            }

            if ([] === $attributes) {
                throw new \RuntimeException(sprintf('When using pretty URLs, the "%s" EasyAdmin dashboard controller must define its route configuration (route name, path) using a #[Route] attribute applied to its "index()" method.', $reflectionClass->getName()));
            }

            if (\count($attributes) > 1) {
                throw new \RuntimeException(sprintf('When using pretty URLs, the "%s" EasyAdmin dashboard controller must define only one #[Route] attribute applied on its "index()" method.', $reflectionClass->getName()));
            }

            $routeAttribute = $attributes[0]->newInstance();
            $config[$reflectionClass->getName()] = [
                'route_name' => $routeAttribute->getName(),
                'route_path' => rtrim($routeAttribute->getPath(), '/'),
            ];
        }

        return $config;
    }
}
