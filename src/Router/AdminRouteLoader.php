<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
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
                'path' => '/detail/{id}',
                'methods' => ['GET'],
            ],
            'new' => [
                'path' => '/new',
                'methods' => ['GET', 'POST'],
            ],
            'edit' => [
                'path' => '/edit/{id}',
                'methods' => ['GET', 'POST'],
            ],
            'delete' => [
                'path' => '/delete/{id}',
                'methods' => ['POST'],
            ],
        ];

        foreach ($this->dashboardControllers as $dashboardController) {
            //$dashboardRouteName = $dashboardController['route'];
            $dashboardFqcn = \get_class($dashboardController);
            $dashboardRouteName = 'admin';
            $crudControllerShortNames = [];
            foreach ($this->crudControllers as $crudController) {
                $crudControllerFqcn = \get_class($crudController);
                $crudControllerShortName = $this->getCrudControllerShortName($crudControllerFqcn, $crudControllerShortNames);
                $crudControllerShortNames[] = $crudControllerShortName;

                $crudControllerRouteName = $dashboardRouteName.'_'.$crudControllerShortName;
                foreach ($crudActionNames as $actionName => $actionConfig) {
                    $crudActionRouteName = $crudControllerRouteName.'_'.$actionName;

                    $path = '/admin/'.$crudControllerShortName.$actionConfig['path'];
                    $defaults = [
                        '_controller' => $crudControllerFqcn.'::'.$actionName,
                    ];
                    $options = [
                        'ea_generated_route' => true,
                        'ea_dashboard_fqcn' => $dashboardFqcn,
                    ];

                    $route = new Route($path, $defaults, [], $options, '', [], $actionConfig['methods']);

                    $collection->add($crudActionRouteName, $route);
                }
            }
        }

        return $collection;
    }

    // Transforms 'App\Controller\Admin\CategoryCrudController' into 'category'
    // To ensure that short names are unique, it adds a number at the end if the name is already in use
    private function getCrudControllerShortName(string $crudControllerFqcn, array $existingNames): string
    {
        $shortName = strtolower(str_replace(['CrudController', 'Controller'], '', (new \ReflectionClass($crudControllerFqcn))->getShortName()));
        $i = 1;
        while (\in_array($shortName, $existingNames, true)) {
            $shortName .= '_'.$i++;
        }

        return $shortName;
    }
}
