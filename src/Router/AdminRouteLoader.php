<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Attribute\Route as RouteAttribute;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class AdminRouteLoader extends Loader
{
    public const ROUTE_TYPE_NAME = 'easyadmin';

    public function __construct(
        private iterable $dashboardControllers,
        private iterable $crudControllers
    ) {
        parent::__construct(null);
    }

    public function supports($resource, string $type = null): bool
    {
        return self::ROUTE_TYPE_NAME === $type;
    }

    public function load($resource, string $type = null): RouteCollection
    {
        return $this->createAdminRoutes();
    }

    private function createAdminRoutes(): RouteCollection
    {
        $collection = new RouteCollection();
        $crudActionNames = [
            'index' => [
                'path' => '/',
                'methods' => ['GET'],
            ],
            'detail' => [
                'path' => '/detail/{entityId}',
                'methods' => ['GET'],
            ],
            'new' => [
                'path' => '/new',
                'methods' => ['GET', 'POST'],
            ],
            'edit' => [
                'path' => '/edit/{entityId}',
                'methods' => ['GET', 'POST', 'PATCH'],
            ],
            'delete' => [
                'path' => '/delete/{entityId}',
                'methods' => ['POST'],
            ],
            'autocomplete' => [
                'path' => '/autocomplete',
                'methods' => ['GET'],
            ],
        ];

        $dashboardRouteConfiguration = $this->getDashboardsRouteConfiguration();
        foreach ($this->dashboardControllers as $dashboardController) {
            $dashboardFqcn = \get_class($dashboardController);
            $dashboardRouteName = $dashboardRouteConfiguration[$dashboardFqcn]['route_name'];
            $crudControllerShortNames = [];
            foreach ($this->crudControllers as $crudController) {
                $crudControllerFqcn = \get_class($crudController);
                $crudControllerShortName = $this->getCrudControllerShortName($crudControllerFqcn, $crudControllerShortNames);
                $crudControllerShortNames[] = $crudControllerShortName;

                $crudControllerRouteName = $dashboardRouteName.'_'.$crudControllerShortName;
                foreach ($crudActionNames as $actionName => $actionConfig) {
                    $crudActionRouteName = $crudControllerRouteName.'_'.$actionName;
                    $path = $dashboardRouteConfiguration[$dashboardFqcn]['route_path'].'/'.$crudControllerShortName.$actionConfig['path'];
                    $defaults = [
                        '_controller' => $crudControllerFqcn.'::'.$actionName,
                    ];
                    $options = [
                        'ea_generated_route' => true,
                        'ea_dashboard_controller_fqcn' => $dashboardFqcn,
                        'ea_crud_controller_fqcn' => $crudControllerFqcn,
                        'ea_action' => $actionName,
                    ];

                    $route = new Route($path, $defaults, [], $options, '', [], $actionConfig['methods']);

                    $collection->add($crudActionRouteName, $route);
                }
            }
        }

        return $collection;
    }

    private function getDashboardsRouteConfiguration(): array
    {
        $config = [];

        foreach ($this->dashboardControllers as $dashboardController) {
            $reflectionClass = new \ReflectionClass($dashboardController);
            $indexMethod = $reflectionClass->getMethod('index');
            $attributes = $indexMethod->getAttributes(RouteAttribute::class);

            if (empty($attributes)) {
                throw new \RuntimeException(sprintf('The "%s" EasyAdmin dashboard controller must define its route configuration (route name, path) using a #[Route] attribute applied to its "index()" method.', $reflectionClass->getName()));
            }

            if (\count($attributes) > 1) {
                throw new \RuntimeException(sprintf('The "%s" EasyAdmin dashboard controller must define only one #[Route] attribute applied on its "index()" method.', $reflectionClass->getName()));
            }

            $routeAttribute = $attributes[0]->newInstance();
            $config[$reflectionClass->getName()] = [
                'route_name' => $routeAttribute->getName(),
                'route_path' => rtrim($routeAttribute->getPath(), '/'),
            ];
        }

        return $config;
    }

    // TODO: allow to change this logic (via service decoration? event listener?) so folks can customize it (e.g. if the use a nested hierarchy and want to add that to the route name)
    // Transforms 'App\Controller\Admin\FooBarBAzCrudController' into 'foo_bar_baz'
    // To ensure that short names are unique, it adds a number at the end if the name is already in use
    private function getCrudControllerShortName(string $crudControllerFqcn, array $existingNames): string
    {
        $shortName = str_replace(['CrudController', 'Controller'], '', (new \ReflectionClass($crudControllerFqcn))->getShortName());
        $shortName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));

        $i = 1;
        while (\in_array($shortName, $existingNames, true)) {
            $shortName .= '_'.$i++;
        }

        return $shortName;
    }
}
