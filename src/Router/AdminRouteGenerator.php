<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminRouteGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class AdminRouteGenerator implements AdminRouteGeneratorInterface
{
    public const CACHE_KEY_ROUTE_TO_FQCN = 'easyadmin.routes.route_to_fqcn';
    public const CACHE_KEY_FQCN_TO_ROUTE = 'easyadmin.routes.fqcn_to_route';

    private const DEFAULT_ROUTES_CONFIG = [
        'index' => [
            'actionName' => 'index',
            'routePath' => '/',
            'routeName' => 'index',
            'methods' => ['GET'],
        ],
        'new' => [
            'actionName' => 'new',
            'routePath' => '/new',
            'routeName' => 'new',
            'methods' => ['GET', 'POST'],
        ],
        'batchDelete' => [
            'actionName' => 'batchDelete',
            'routePath' => '/batch-delete',
            'routeName' => 'batch_delete',
            'methods' => ['POST'],
        ],
        'autocomplete' => [
            'actionName' => 'autocomplete',
            'routePath' => '/autocomplete',
            'routeName' => 'autocomplete',
            'methods' => ['GET'],
        ],
        'renderFilters' => [
            'actionName' => 'renderFilters',
            'routePath' => '/render-filters',
            'routeName' => 'render_filters',
            'methods' => ['GET'],
        ],
        'edit' => [
            'actionName' => 'edit',
            'routePath' => '/{entityId}/edit',
            'routeName' => 'edit',
            'methods' => ['GET', 'POST', 'PATCH'],
        ],
        'delete' => [
            'actionName' => 'delete',
            'routePath' => '/{entityId}/delete',
            'routeName' => 'delete',
            'methods' => ['POST'],
        ],
        'detail' => [
            'actionName' => 'detail',
            'routePath' => '/{entityId}',
            'routeName' => 'detail',
            'methods' => ['GET'],
        ],
    ];

    private ?bool $applicationUsesPrettyUrls = null;

    /**
     * @param iterable<DashboardControllerInterface> $dashboardControllers
     * @param iterable<CrudControllerInterface>      $crudControllers
     * @param iterable<object>                       $adminRouteControllers Controllers with the #[AdminRoute] attribute
     */
    public function __construct(
        private readonly iterable $dashboardControllers,
        private readonly iterable $crudControllers,
        private readonly CacheItemPoolInterface $cache,
        private readonly Filesystem $filesystem,
        private readonly string $buildDir,
        private readonly string $defaultLocale,
        private readonly iterable $adminRouteControllers = [],
    ) {
    }

    public function generateAll(): RouteCollection
    {
        $collection = new RouteCollection();
        $adminRoutes = $this->generateAdminRoutes();

        foreach ($adminRoutes as $routeName => $route) {
            $collection->add($routeName, $route);
        }

        // this dumps all admin routes in a performance-optimized format to later
        // find them quickly without having to use Symfony's router service
        $this->saveAdminRoutesInCache($adminRoutes);

        return $collection;
    }

    // Temporary utility method to be removed in EasyAdmin 5, when the pretty URLs will be mandatory
    // TODO: remove this method in EasyAdmin 5.x
    public function usesPrettyUrls(): bool
    {
        return $this->applicationUsesPrettyUrls ??= $this->filesystem->exists(sprintf('%s/%s', $this->buildDir, AdminRouteLoader::PRETTY_URLS_CONTEXT_FILE_NAME));
    }

    public function findRouteName(?string $dashboardFqcn = null, ?string $crudControllerFqcn = null, ?string $actionName = null): ?string
    {
        $adminRoutes = $this->cache->getItem(self::CACHE_KEY_FQCN_TO_ROUTE)->get();

        if (null === $dashboardFqcn) {
            $dashboardControllers = iterator_to_array($this->dashboardControllers);
            $dashboardFqcn = $dashboardControllers[array_key_first($dashboardControllers)]::class;
        }

        return $adminRoutes[$dashboardFqcn][$crudControllerFqcn ?? ''][$actionName ?? ''] ?? null;
    }

    /**
     * @return array<string, Route>
     */
    private function generateAdminRoutes(): array
    {
        /** @var array<string, Route> $adminRoutes Stores the collection of admin routes created for the app */
        $adminRoutes = [];
        /** @var array<string> $addedRouteNames Temporary cache that stores the route names to ensure that we don't add duplicated admin routes */
        $addedRouteNames = [];

        // create the routes for the CRUD controllers and actions
        foreach ($this->dashboardControllers as $dashboardController) {
            $dashboardFqcn = $dashboardController::class;
            [$allowedCrudControllers, $deniedCrudControllers] = $this->getAllowedAndDeniedControllers($dashboardFqcn);
            $defaultRoutesConfig = $this->getDefaultRoutesConfig($dashboardFqcn);
            $dashboardRouteConfig = $this->getDashboardsRouteConfig()[$dashboardFqcn];

            // first, create the routes of the dashboards if they are defined with the #[AdminDashboard] attribute instead of the Symfony #[Route] attribute
            if (null !== $adminRouteData = $this->createDashboardRoute($dashboardFqcn)) {
                $adminRoutes[$adminRouteData['routeName']] = $adminRouteData['route'];
                $addedRouteNames[] = $adminRouteData['routeName'];
            }

            // then, create the routes of the CRUD controllers associated with the dashboard
            foreach ($this->crudControllers as $crudController) {
                $crudControllerFqcn = $crudController::class;

                if (null !== $allowedCrudControllers && !\in_array($crudControllerFqcn, $allowedCrudControllers, true)) {
                    continue;
                }

                if (null !== $deniedCrudControllers && \in_array($crudControllerFqcn, $deniedCrudControllers, true)) {
                    continue;
                }

                $crudControllerRouteConfig = $this->getCrudControllerRouteConfig($crudControllerFqcn);
                $actionsRouteConfig = array_replace_recursive($defaultRoutesConfig, $this->getCustomActionsConfig($crudControllerFqcn));

                // if a controller overrides the name of a route for a built-in action (e.g. 'update' for edit() action)
                // remove the default route config for that built-in action (e.g. 'edit'); otherwise, we'd generate two
                // different routes for the same built-in action: the custom one ('update') and the default one ('edit')
                $builtInActionKeys = array_keys(self::DEFAULT_ROUTES_CONFIG);
                foreach ($actionsRouteConfig as $key => $config) {
                    // $actionsRouteConfig contains both the built-in and custom routes; skip entries that are built-in
                    if (\in_array($key, $builtInActionKeys, true)) {
                        continue;
                    }

                    $actionName = $config['actionName'];

                    // if this custom route is for a built-in action (e.g. 'edit'), remove the default route entry associated to it
                    if (isset($actionsRouteConfig[$actionName]) && \in_array($actionName, $builtInActionKeys, true)) {
                        unset($actionsRouteConfig[$actionName]);
                    }
                }

                // by default, the 'detail' route uses a catch-all route pattern (/{entityId});
                // so, if the user hasn't customized the 'detail' route path, we need to sort the actions
                // to make sure that the 'detail' action is always the last one
                if (isset($actionsRouteConfig['detail']) && '/{entityId}' === $actionsRouteConfig['detail']['routePath']) {
                    uasort($actionsRouteConfig, static function ($a, $b) {
                        $aRouteName = $a['routeName'] ?? '';
                        $bRouteName = $b['routeName'] ?? '';

                        return match (true) {
                            'detail' === $aRouteName => 1,
                            'detail' === $bRouteName => -1,
                            default => 0,
                        };
                    });
                }

                foreach (array_keys($actionsRouteConfig) as $actionName) {
                    $actionRouteConfig = $actionsRouteConfig[$actionName];
                    $actionNameSnakeCase = strtolower(preg_replace('/[A-Z]/', '_$0', $actionRouteConfig['actionName']));
                    $actionNameSlug = strtolower(preg_replace('/[A-Z]/', '-$0', $actionRouteConfig['actionName']));

                    $adminRoutePath = rtrim(sprintf('%s/%s/%s', $dashboardRouteConfig['routePath'], $crudControllerRouteConfig['routePath'], ltrim($actionRouteConfig['routePath'] ?? $actionNameSlug, '/')), '/');
                    $adminRouteName = sprintf('%s_%s_%s', $dashboardRouteConfig['routeName'], $crudControllerRouteConfig['routeName'], $actionRouteConfig['routeName'] ?? $actionNameSnakeCase);

                    if (\in_array($adminRouteName, $addedRouteNames, true)) {
                        throw new \RuntimeException(sprintf('When using pretty URLs, all CRUD controller actions must generate unique route names. However, in your application there are at least two CRUD actions that would generate the same route "%s". This happens (1) when your application has at least two controllers with the same class name (even if they are in different namespaces) or (2) when you applied more than one #[AdminRoute] attribute to the same CRUD controller action and didn\'t define a custom route name at least for one of them.', $adminRouteName));
                    }

                    $defaults = [
                        '_locale' => $this->defaultLocale,
                        '_controller' => $crudControllerFqcn.'::'.$actionRouteConfig['actionName'],
                        EA::ROUTE_CREATED_BY_EASYADMIN => true,
                        EA::DASHBOARD_CONTROLLER_FQCN => $dashboardFqcn,
                        EA::CRUD_CONTROLLER_FQCN => $crudControllerFqcn,
                        EA::CRUD_ACTION => $actionRouteConfig['actionName'],
                    ];

                    $adminRoute = new Route($adminRoutePath, defaults: $defaults, methods: $actionRouteConfig['methods']);
                    $adminRoutes[$adminRouteName] = $adminRoute;
                    $addedRouteNames[] = $adminRouteName;
                }
            }
        }

        // create the routes for the controllers that use the #[AdminRoute] attribute
        foreach ($this->adminRouteControllers as $controller) {
            $controllerFqcn = \is_object($controller) ? $controller::class : $controller;
            $reflectionClass = new \ReflectionClass($controllerFqcn);

            // if the class is a CRUD controller, skip it because all the routes (default and custom) were already
            // created before when creating the regular CRUD controller routes
            if ($reflectionClass->implementsInterface(CrudControllerInterface::class)) {
                continue;
            }

            // check first for class-level attributes
            $classAttributes = $reflectionClass->getAttributes(AdminRoute::class);
            $classAdminRoute = null;
            $hasMethodRoutes = false;

            // Check if there are any method-level routes
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ([] !== $method->getAttributes(AdminRoute::class)) {
                    $hasMethodRoutes = true;
                    break;
                }
            }

            if ([] !== $classAttributes) {
                // Only create routes for class-level attributes if there are NO method-level routes
                // If there are method routes, the class attribute is used only as a prefix
                if (!$hasMethodRoutes) {
                    // the controller must be invokable when using class-level routes without method routes
                    if (!method_exists($controller, '__invoke')) {
                        throw new \RuntimeException(sprintf('When applying the #[AdminRoute] attribute only to the controller class (as in "%s") without any methods having #[AdminRoute], the controller must be invokable (i.e. it must define the "__invoke()" method).', $controllerFqcn));
                    }

                    // AdminRoute attribute is repeatable, so there can be more than one
                    foreach ($classAttributes as $classAttribute) {
                        /** @var AdminRoute $currentClassAdminRoute */
                        $currentClassAdminRoute = $classAttribute->newInstance();

                        // both name and path must be defined to create a route
                        if (null === $currentClassAdminRoute->name || null === $currentClassAdminRoute->path) {
                            throw new \RuntimeException(sprintf('The #[AdminRoute] attribute applied to the "%s" controller class must define both "name" and "path" arguments to generate its route (because the controller has no other methods with #[AdminRoute] and therefore, the class-level attribute doesn\'t work as a path/name prefix of other actions but as a standalone route).', $controllerFqcn));
                        }

                        // create route for the entire controller
                        foreach ($this->dashboardControllers as $dashboardController) {
                            $dashboardFqcn = $dashboardController::class;
                            $allowedDashboards = $currentClassAdminRoute->allowedDashboards;
                            $deniedDashboards = $currentClassAdminRoute->deniedDashboards;

                            // skip if not in allowed dashboards (when restrictions are set)
                            if (\is_array($allowedDashboards) && !\in_array($dashboardFqcn, $allowedDashboards, true)) {
                                continue;
                            }

                            // skip if in denied dashboards (when restrictions are set)
                            if (\is_array($deniedDashboards) && \in_array($dashboardFqcn, $deniedDashboards, true)) {
                                continue;
                            }

                            $dashboardRouteConfig = $this->getDashboardsRouteConfig()[$dashboardFqcn];

                            $adminRoutePath = rtrim(sprintf('%s/%s', $dashboardRouteConfig['routePath'], ltrim($currentClassAdminRoute->path, '/')), '/');
                            $adminRouteName = sprintf('%s_%s', $dashboardRouteConfig['routeName'], ltrim($currentClassAdminRoute->name, '_'));

                            if (\in_array($adminRouteName, $addedRouteNames, true)) {
                                throw new \RuntimeException(sprintf('The #[AdminRoute] attribute applied to the "%s" controller would create an admin route called "%s", which already exists. You must change the "routeName" argument to generate a different route name.', $controllerFqcn, $adminRouteName));
                            }

                            $adminRoute = $this->createRouteForAdminAttribute($currentClassAdminRoute, $adminRoutePath, $dashboardFqcn, $controllerFqcn, '__invoke');

                            $adminRoutes[$adminRouteName] = $adminRoute;
                            $addedRouteNames[] = $adminRouteName;
                        }
                    }
                }

                // store the first class attribute for use as a prefix with method routes
                /** @var AdminRoute $classAdminRoute */
                $classAdminRoute = $classAttributes[0]->newInstance();
            }

            // now, check for method-level attributes
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $methodAttributes = $method->getAttributes(AdminRoute::class);
                if ([] === $methodAttributes) {
                    continue;
                }

                foreach ($methodAttributes as $methodAttribute) {
                    /** @var AdminRoute $methodAdminRoute */
                    $methodAdminRoute = $methodAttribute->newInstance();

                    // create route for the method
                    foreach ($this->dashboardControllers as $dashboardController) {
                        $dashboardFqcn = $dashboardController::class;

                        // Dashboard restrictions inheritance:
                        // - false: Not set - inherit from class attribute
                        // - null: Explicitly allow all (for allowed) or deny none (for denied)
                        // - []: Explicitly allow/deny none
                        // - [...]: Allow/deny specific dashboards
                        $allowedDashboards = false !== $methodAdminRoute->allowedDashboards
                            ? $methodAdminRoute->allowedDashboards
                            : $classAdminRoute?->allowedDashboards;

                        $deniedDashboards = false !== $methodAdminRoute->deniedDashboards
                            ? $methodAdminRoute->deniedDashboards
                            : $classAdminRoute?->deniedDashboards;

                        // skip if not in allowed dashboards (when restrictions are set)
                        if (\is_array($allowedDashboards) && !\in_array($dashboardFqcn, $allowedDashboards, true)) {
                            continue;
                        }

                        // skip if in denied dashboards (when restrictions are set)
                        if (\is_array($deniedDashboards) && \in_array($dashboardFqcn, $deniedDashboards, true)) {
                            continue;
                        }

                        $dashboardRouteConfig = $this->getDashboardsRouteConfig()[$dashboardFqcn];

                        // build the route path: dashboard + class prefix (if any) + method path
                        $routePathParts = [$dashboardRouteConfig['routePath']];

                        if (null !== $classAdminRoute?->path) {
                            // Use the explicit class-level AdminRoute path
                            $routePathParts[] = ltrim($classAdminRoute->path, '/');
                        }

                        if (null !== $methodAdminRoute->path) {
                            $routePathParts[] = ltrim($methodAdminRoute->path, '/');
                        }
                        $adminRoutePath = rtrim(implode('/', $routePathParts), '/');

                        // build the route name: dashboard + class prefix (if any) + method name
                        $routeNameParts = [$dashboardRouteConfig['routeName']];

                        if (null !== $classAdminRoute?->name) {
                            // Use the explicit class-level AdminRoute name
                            $routeNameParts[] = ltrim($classAdminRoute->name, '_');
                        }

                        if (null !== $methodAdminRoute->name) {
                            $routeNameParts[] = ltrim($methodAdminRoute->name, '_');
                        }
                        $adminRouteName = implode('_', $routeNameParts);

                        if (\in_array($adminRouteName, $addedRouteNames, true)) {
                            throw new \RuntimeException(sprintf('The #[AdminRoute] attribute applied to the "%s" method of the "%s" controller would create an admin route called "%s", which already exists. You must change the "routeName" argument to generate a different route name.', $method->name, $controllerFqcn, $adminRouteName));
                        }

                        // merge route options: method options override class options
                        $mergedRouteOptions = array_merge(
                            $classAdminRoute->options ?? [],
                            $methodAdminRoute->options
                        );

                        // create a new AdminRoute with merged configuration
                        $mergedAdminRoute = new AdminRoute(
                            path: $methodAdminRoute->path,
                            name: $methodAdminRoute->name,
                            options: $mergedRouteOptions,
                            allowedDashboards: $allowedDashboards,
                            deniedDashboards: $deniedDashboards
                        );

                        $adminRoute = $this->createRouteForAdminAttribute($mergedAdminRoute, $adminRoutePath, $dashboardFqcn, $controllerFqcn, $method->name);

                        $adminRoutes[$adminRouteName] = $adminRoute;
                        $addedRouteNames[] = $adminRouteName;
                    }
                }
            }
        }

        return $adminRoutes;
    }

    private function createRouteForAdminAttribute(AdminRoute $adminRouteAttribute, string $routePath, string $dashboardFqcn, string $controllerFqcn, string $methodName): Route
    {
        $route = new Route($routePath);

        $routeOptions = $adminRouteAttribute->options;

        if (isset($routeOptions['requirements'])) {
            $route->setRequirements($routeOptions['requirements']);
        }
        if (isset($routeOptions['host'])) {
            $route->setHost($routeOptions['host']);
        }
        if (isset($routeOptions['methods'])) {
            $route->setMethods($routeOptions['methods']);
        }
        if (isset($routeOptions['schemes'])) {
            $route->setSchemes($routeOptions['schemes']);
        }
        if (isset($routeOptions['condition'])) {
            $route->setCondition($routeOptions['condition']);
        }

        $defaults = $routeOptions['defaults'] ?? [];
        if (isset($routeOptions['locale'])) {
            $defaults['_locale'] = $routeOptions['locale'];
        }
        if (isset($routeOptions['format'])) {
            $defaults['_format'] = $routeOptions['format'];
        }
        if (isset($routeOptions['stateless'])) {
            $defaults['_stateless'] = $routeOptions['stateless'];
        }
        $defaults['_controller'] = $controllerFqcn.'::'.$methodName;
        $defaults[EA::ROUTE_CREATED_BY_EASYADMIN] = true;
        $defaults[EA::DASHBOARD_CONTROLLER_FQCN] = $dashboardFqcn;
        $defaults[EA::CRUD_CONTROLLER_FQCN] = $controllerFqcn;
        $defaults[EA::CRUD_ACTION] = $methodName;
        $route->setDefaults($defaults);

        if (isset($routeOptions['utf8'])) {
            $routeOptions['options']['utf8'] = $routeOptions['utf8'];
        }
        if (isset($routeOptions['options'])) {
            $route->setOptions($routeOptions['options']);
        }

        return $route;
    }

    /**
     * @param class-string<DashboardControllerInterface> $dashboardFqcn
     *
     * @return array{0: class-string[]|null, 1: class-string[]|null}
     */
    private function getAllowedAndDeniedControllers(string $dashboardFqcn): array
    {
        if (null === $attribute = $this->getPhpAttributeInstance($dashboardFqcn, AdminDashboard::class)) {
            return [null, null];
        }

        if (null !== $attribute->allowedControllers && null !== $attribute->deniedControllers) {
            throw new \RuntimeException(sprintf('In the #[AdminDashboard] attribute of the "%s" dashboard controller, you cannot define both "allowedControllers" and "deniedControllers" at the same time because they are the exact opposite. Use only one of them.', $dashboardFqcn));
        }

        return [$attribute->allowedControllers, $attribute->deniedControllers];
    }

    /**
     * @param class-string<DashboardControllerInterface> $dashboardFqcn
     *
     * @return array<string, array{routeName: string, routePath: string, methods?: array<string>}>
     */
    private function getDefaultRoutesConfig(string $dashboardFqcn): array
    {
        if (null === $dashboardAttribute = $this->getPhpAttributeInstance($dashboardFqcn, AdminDashboard::class)) {
            return self::DEFAULT_ROUTES_CONFIG;
        }

        if (null === $customRoutesConfig = $dashboardAttribute->routes) {
            return self::DEFAULT_ROUTES_CONFIG;
        }

        foreach ($customRoutesConfig as $action => $customRouteConfig) {
            if (\count(array_diff(array_keys($customRouteConfig), ['routePath', 'routeName'])) > 0) {
                throw new \RuntimeException(sprintf('In the #[AdminDashboard] attribute of the "%s" dashboard controller, the route configuration for the "%s" action defines some unsupported keys. You can only define these keys: "routePath" and "routeName".', $dashboardFqcn, $action));
            }

            if (isset($customRouteConfig['routeName']) && 1 !== preg_match('/^[a-zA-Z0-9_-]+$/', $customRouteConfig['routeName'])) {
                throw new \RuntimeException(sprintf('In the #[AdminDashboard] attribute of the "%s" dashboard controller, the route name "%s" for the "%s" action is not valid. It can only contain letter, numbers, dashes, and underscores.', $dashboardFqcn, $customRouteConfig['routeName'], $action));
            }

            if (isset($customRouteConfig['routePath']) && \in_array($action, ['edit', 'detail', 'delete'], true) && !str_contains($customRouteConfig['routePath'], '{entityId}')) {
                throw new \RuntimeException(sprintf('In the #[AdminDashboard] attribute of the "%s" dashboard controller, the path for the "%s" action must contain the "{entityId}" placeholder.', $action, $dashboardFqcn));
            }
        }

        return array_replace_recursive(self::DEFAULT_ROUTES_CONFIG, $customRoutesConfig);
    }

    /**
     * @return array<string, array{routeName: string, routePath: string}>
     */
    private function getDashboardsRouteConfig(): array
    {
        $config = [];

        foreach ($this->dashboardControllers as $dashboardController) {
            $reflectionClass = new \ReflectionClass($dashboardController);

            // first, check if the dashboard uses the #[AdminDashboard] attribute to define its route configuration;
            // this is the recommended way for modern EasyAdmin applications
            $attributes = $reflectionClass->getAttributes(AdminDashboard::class);
            $usesAdminDashboardAttribute = [] !== $attributes;
            if ($usesAdminDashboardAttribute) {
                $adminDashboardAttribute = $attributes[0]->newInstance();
                $routeName = $adminDashboardAttribute->routeName;
                $routePath = $adminDashboardAttribute->routePath;
                if (null !== $routePath) {
                    $routePath = rtrim($adminDashboardAttribute->routePath, '/');
                }

                if (null !== $routeName && null !== $routePath) {
                    $config[$reflectionClass->getName()] = [
                        'routeName' => $routeName,
                        'routePath' => $routePath,
                    ];

                    continue;
                } else {
                    @trigger_deprecation(
                        'easycorp/easyadmin-bundle',
                        '4.24.0',
                        'The "%s" dashboard controller applies the #[AdminDashboard] attribute, but it doesn\'t use it to define the route path and route name of the dashboard. Using the default #[Route] attribute from Symfony on the "index()" method of the dashboard instead of the #[AdminDashboard] attribute (e.g. #[AdminDashboard(routePath: \'/admin\', routeName: \'admin\')]) is deprecated and it will no longer work in EasyAdmin 5.0.0.',
                        $reflectionClass->getName()
                    );
                }
            } else {
                @trigger_deprecation(
                    'easycorp/easyadmin-bundle',
                    '4.24.0',
                    'The "%s" dashboard controller does not apply the #[AdminDashboard] attribute. Applying this attribute is the recommended way to define the route path and route name of the dashboard, instead of using the default #[Route] attribute from Symfony (e.g. #[AdminDashboard(routePath: \'/admin\', routeName: \'admin\')]). Not applying the #[AdminDashboard] attribute is deprecated because it will be mandatory in EasyAdmin 5.0.0.',
                    $reflectionClass->getName()
                );
            }

            // this is the legacy way to define the route configuration of the dashboard: using the Symfony #[Route]
            // attribute on the "index()" method of the dashboard controller;
            // for BC reasons, the Symfony Route attribute is available under two different namespaces;
            // true first the recommended namespace and then fall back to the legacy namespace
            $indexMethod = $reflectionClass->getMethod('index');
            $attributes = $indexMethod->getAttributes('Symfony\Component\Routing\Attribute\Route');
            if ([] === $attributes) {
                $attributes = $indexMethod->getAttributes('Symfony\Component\Routing\Annotation\Route');
            }

            if ([] === $attributes) {
                throw new \RuntimeException(sprintf('When using pretty URLs, it\'s recommended to define the dashboard route name and path using the #[AdminDashboard] attribute on the dashboard class. Alternatively, you can apply Symfony\'s #[Route] attribute to the "index()" method of the "%s" controller. However, this alternative will no longer work in EasyAdmin 5.0.', $reflectionClass->getName()));
            }

            if (\count($attributes) > 1) {
                throw new \RuntimeException(sprintf('When using pretty URLs, it\'s recommended to define the dashboard route name and path using the #[AdminDashboard] attribute on the dashboard class. Alternatively, you can apply Symfony\'s #[Route] attribute to the "index()" method of the "%s" controller. In that case, you cannot apply more than one #[Route] attribute to the "index()" method. Also, this alternative will no longer work in EasyAdmin 5.0.', $reflectionClass->getName()));
            }

            $routeAttribute = $attributes[0]->newInstance();
            $config[$reflectionClass->getName()] = [
                // Symfony 8 removed the getName() and getPath() methods in favor of public properties
                /** @phpstan-ignore-next-line */
                'routeName' => method_exists($routeAttribute, 'getName') ? $routeAttribute->getName() : $routeAttribute->name,
                /** @phpstan-ignore-next-line */
                'routePath' => rtrim(method_exists($routeAttribute, 'getPath') ? $routeAttribute->getPath() : $routeAttribute->path, '/'),
            ];
        }

        return $config;
    }

    /**
     * @param class-string<CrudControllerInterface> $crudControllerFqcn
     *
     * @return array{routeName: string, routePath: string}
     */
    private function getCrudControllerRouteConfig(string $crudControllerFqcn): array
    {
        $crudControllerConfig = [];

        $reflectionClass = new \ReflectionClass($crudControllerFqcn);

        // first, check for #[AdminRoute] attribute
        $adminRouteAttributes = $reflectionClass->getAttributes(AdminRoute::class);
        if ([] !== $adminRouteAttributes) {
            /** @var AdminRoute $adminRouteInstance */
            $adminRouteInstance = $adminRouteAttributes[0]->newInstance();

            if (null !== $adminRouteInstance->path) {
                $crudControllerConfig['routePath'] = trim($adminRouteInstance->path, '/');
            }

            if (null !== $adminRouteInstance->name) {
                if (1 !== preg_match('/^[a-zA-Z0-9_-]+$/', $adminRouteInstance->name)) {
                    throw new \RuntimeException(sprintf('In the #[AdminRoute] attribute of the "%s" CRUD controller, the route name "%s" is not valid. It can only contain letters, numbers, dashes, and underscores.', $crudControllerFqcn, $adminRouteInstance->name));
                }

                $crudControllerConfig['routeName'] = trim($adminRouteInstance->name, '_');
            }
        } else {
            // fallback to deprecated #[AdminCrud] attribute for backward compatibility
            $attributes = $reflectionClass->getAttributes(AdminCrud::class);
            $attribute = $attributes[0] ?? null;

            if (null !== $attribute) {
                trigger_deprecation(
                    'easycorp/easyadmin-bundle',
                    '4.25.0',
                    'The #[AdminCrud] attribute used in "%s" is deprecated. Use #[AdminRoute] instead.',
                    $crudControllerFqcn,
                );

                /** @var AdminCrud $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                if (\count(array_diff(array_keys($attribute->getArguments()), ['routePath', 'routeName', 0, 1])) > 0) {
                    throw new \RuntimeException(sprintf('In the #[AdminCrud] attribute of the "%s" CRUD controller, the route configuration defines some unsupported keys. You can only define these keys: "routePath" and "routeName".', $crudControllerFqcn));
                }

                if (null !== $attributeInstance->routePath) {
                    $crudControllerConfig['routePath'] = trim($attributeInstance->routePath, '/');
                }

                if (null !== $attributeInstance->routeName) {
                    if (1 !== preg_match('/^[a-zA-Z0-9_-]+$/', $attributeInstance->routeName)) {
                        throw new \RuntimeException(sprintf('In the #[AdminCrud] attribute of the "%s" CRUD controller, the route name "%s" is not valid. It can only contain letter, numbers, dashes, and underscores.', $crudControllerFqcn, $attributeInstance->routeName));
                    }

                    $crudControllerConfig['routeName'] = trim($attributeInstance->routeName, '_');
                }
            }
        }

        // if the CRUD controller doesn't define any or all of the route configuration,
        // use the default values based on the controller's class name
        if (!\array_key_exists('routePath', $crudControllerConfig)) {
            $crudControllerConfig['routePath'] = trim($this->transformCrudControllerNameToKebabCase($crudControllerFqcn), '/');
        }
        if (!\array_key_exists('routeName', $crudControllerConfig)) {
            $crudControllerConfig['routeName'] = trim($this->transformCrudControllerNameToSnakeCase($crudControllerFqcn), '_');
        }

        return $crudControllerConfig;
    }

    /**
     * @param class-string<CrudControllerInterface> $crudControllerFqcn
     *
     * @return array<string, array{routeName?: string, routePath?: string, methods?: array<string>}>
     */
    private function getCustomActionsConfig(string $crudControllerFqcn): array
    {
        $customActionsConfig = [];
        $reflectionClass = new \ReflectionClass($crudControllerFqcn);
        $methods = $reflectionClass->getMethods();

        foreach ($methods as $method) {
            $action = $method->getName();

            // first, check for #[AdminRoute] attribute (it is repeatable, so there can be more than one)
            $adminRouteAttributes = $method->getAttributes(AdminRoute::class);
            if ([] !== $adminRouteAttributes) {
                // process each AdminRoute attribute separately to support multiple routes per action
                foreach ($adminRouteAttributes as $index => $adminRouteAttribute) {
                    /** @var AdminRoute $adminRouteInstance */
                    $adminRouteInstance = $adminRouteAttribute->newInstance();

                    // each route can define more than one route, so we cannot use the action name as the array key
                    $routeId = $action.'_route_'.++$index;

                    if (null !== $adminRouteInstance->path) {
                        if (\in_array($action, ['edit', 'detail', 'delete'], true) && !str_contains($adminRouteInstance->path, '{entityId}')) {
                            throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminRoute] attribute applied to the "%s()" action is missing the "{entityId}" placeholder in its route path.', $crudControllerFqcn, $action));
                        }

                        $customActionsConfig[$routeId]['routePath'] = $adminRouteInstance->path;
                    } else {
                        $customActionsConfig[$routeId]['routePath'] = null;
                    }

                    if (null !== $adminRouteInstance->name) {
                        if (1 !== preg_match('/^[a-zA-Z0-9_-]+$/', $adminRouteInstance->name)) {
                            throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminRoute] attribute applied to the "%s()" action defines an invalid route name: "%s". Valid route names can only contain letters, numbers, dashes, and underscores.', $crudControllerFqcn, $action, $adminRouteInstance->name));
                        }

                        $customActionsConfig[$routeId]['routeName'] = trim($adminRouteInstance->name, '_');
                    } else {
                        $customActionsConfig[$routeId]['routeName'] = null;
                    }

                    // handle methods from routeOptions or use smart defaults
                    $methods = $adminRouteInstance->options['methods'] ?? null;
                    if (null === $methods) {
                        // smart defaults: built-in actions have fixed methods, custom actions default to GET and POST
                        if (\in_array($action, ['index', 'detail'], true)) {
                            $methods = ['GET'];
                        } elseif (\in_array($action, ['new', 'edit', 'delete', 'batchDelete'], true)) {
                            $methods = ['GET', 'POST'];
                        } else {
                            // custom actions default to GET and POST
                            $methods = ['GET', 'POST'];
                        }
                    }

                    if (\is_string($methods)) {
                        $methods = [$methods];
                    }

                    // validate HTTP methods
                    $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'];
                    foreach ($methods as $httpMethod) {
                        if (!\in_array(strtoupper($httpMethod), $allowedMethods, true)) {
                            throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminRoute] attribute applied to the "%s()" action includes "%s" as part of its HTTP methods. However, the only allowed HTTP methods are: %s', $crudControllerFqcn, $action, $httpMethod, implode(', ', $allowedMethods)));
                        }
                    }

                    $customActionsConfig[$routeId]['methods'] = array_map('strtoupper', $methods);

                    // store the actual action name for the route generation
                    $customActionsConfig[$routeId]['actionName'] = $action;
                }

                continue; // used to skip checking for deprecated AdminAction
            }

            // fallback to deprecated #[AdminAction] attribute for backward compatibility
            $attributes = $method->getAttributes(AdminAction::class);
            if ([] === $attributes) {
                continue;
            }

            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.25.0',
                'The #[AdminAction] attribute used in "%s::%s()" is deprecated. Use #[AdminRoute] instead.',
                $crudControllerFqcn, $action
            );

            $attribute = $attributes[0];
            /** @var AdminAction $attributeInstance */
            $attributeInstance = $attribute->newInstance();

            if (\count(array_diff(array_keys($attribute->getArguments()), ['routePath', 'routeName', 'methods', 0, 1, 2])) > 0) {
                throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminAction] attribute applied to the "%s()" action includes some unsupported keys. You can only define these keys: "routePath", "routeName", and "methods".', $crudControllerFqcn, $action));
            }

            $customActionsConfig[$action]['actionName'] = $action;

            if (null !== $attributeInstance->routePath) {
                if (\in_array($action, ['edit', 'detail', 'delete'], true) && !str_contains($attributeInstance->routePath, '{entityId}')) {
                    throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminAction] attribute applied to the "%s()" action is missing the "{entityId}" placeholder in its route path.', $crudControllerFqcn, $action));
                }

                $customActionsConfig[$action]['routePath'] = trim($attributeInstance->routePath, '/');
            }

            if (null !== $attributeInstance->routeName) {
                if (1 !== preg_match('/^[a-zA-Z0-9_-]+$/', $attributeInstance->routeName)) {
                    throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminAction] attribute applied to the "%s()" action defines an invalid route name: "%s". Valid route names can only contain letters, numbers, dashes, and underscores.', $crudControllerFqcn, $action, $attributeInstance->routeName));
                }

                $customActionsConfig[$action]['routeName'] = trim($attributeInstance->routeName, '_');
            }

            if (\array_key_exists('methods', $attribute->getArguments()) && null !== $attribute->getArguments()['methods'] && \in_array($action, ['index', 'new', 'edit', 'detail', 'delete'], true)) {
                throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminAction] attribute applied to the "%s()" action cannot define the "methods" argument because these are built-in EasyAdmin actions and have fixed HTTP methods.', $crudControllerFqcn, $action));
            }

            if (null !== $attributeInstance->methods) {
                $allowedMethods = ['GET', 'POST', 'PATCH', 'PUT'];
                foreach ($attributeInstance->methods as $httpMethod) {
                    if (!\in_array(strtoupper($httpMethod), $allowedMethods, true)) {
                        throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminAction] attribute applied to the "%s()" action includes "%s" as part of its HTTP methods. However, the only allowed HTTP methods are: %s', $crudControllerFqcn, $action, $httpMethod, implode(', ', $allowedMethods)));
                    }
                }

                $customActionsConfig[$action]['methods'] = $attributeInstance->methods;
            }
        }

        return $customActionsConfig;
    }

    /**
     * @param class-string<DashboardControllerInterface> $dashboardFqcn
     *
     * @return array{routeName: string, route: Route}|null
     */
    private function createDashboardRoute(string $dashboardFqcn): ?array
    {
        /** @var AdminDashboard|null $attribute */
        $attribute = $this->getPhpAttributeInstance($dashboardFqcn, AdminDashboard::class);
        if (null === $attribute) {
            return null;
        }

        if (null === $attribute->routeName || null === $attribute->routePath) {
            // TODO: in EasyAdmin 5.0, this should throw an exception instead of returning an empty array
            return null;
        }

        $routeName = $attribute->routeName;
        $routePath = $attribute->routePath;
        $routeOptions = $attribute->routeOptions;

        $route = new Route($routePath);

        if (isset($routeOptions['requirements'])) {
            $route->setRequirements($routeOptions['requirements']);
        }
        if (isset($routeOptions['host'])) {
            $route->setHost($routeOptions['host']);
        }
        if (isset($routeOptions['methods'])) {
            $route->setMethods($routeOptions['methods']);
        }
        if (isset($routeOptions['schemes'])) {
            $route->setSchemes($routeOptions['schemes']);
        }
        if (isset($routeOptions['condition'])) {
            $route->setCondition($routeOptions['condition']);
        }

        $defaults = $routeOptions['defaults'] ?? [];
        if (isset($routeOptions['locale'])) {
            $defaults['_locale'] = $routeOptions['locale'];
        }
        if (isset($routeOptions['format'])) {
            $defaults['_format'] = $routeOptions['format'];
        }
        if (isset($routeOptions['stateless'])) {
            $defaults['_stateless'] = $routeOptions['stateless'];
        }
        $defaults['_controller'] = $dashboardFqcn.'::index';
        $defaults[EA::ROUTE_CREATED_BY_EASYADMIN] = true;
        $defaults[EA::DASHBOARD_CONTROLLER_FQCN] = $dashboardFqcn;
        $defaults[EA::CRUD_CONTROLLER_FQCN] = null;
        $defaults[EA::CRUD_ACTION] = null;
        $route->setDefaults($defaults);

        if (isset($routeOptions['utf8'])) {
            $routeOptions['options']['utf8'] = $routeOptions['utf8'];
        }
        if (isset($routeOptions['options'])) {
            $route->setOptions($routeOptions['options']);
        }

        return ['routeName' => $routeName, 'route' => $route];
    }

    /**
     * @template T of object
     *
     * @param class-string    $classFqcn
     * @param class-string<T> $attributeFqcn
     *
     * @return T|null
     */
    private function getPhpAttributeInstance(string $classFqcn, string $attributeFqcn): ?object
    {
        $reflectionClass = new \ReflectionClass($classFqcn);
        if ([] === $attributes = $reflectionClass->getAttributes($attributeFqcn)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Transforms 'App\Controller\Admin\FooBarBazCrudController' into 'foo-bar-baz'.
     *
     * @param class-string<CrudControllerInterface> $crudControllerFqcn
     */
    private function transformCrudControllerNameToKebabCase(string $crudControllerFqcn): string
    {
        $cleanShortName = str_replace(['CrudController', 'Controller'], '', (new \ReflectionClass($crudControllerFqcn))->getShortName());
        $snakeCaseName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $cleanShortName));

        return $snakeCaseName;
    }

    /**
     * Transforms 'App\Controller\Admin\FooBarBazCrudController' into 'foo_bar_baz'.
     *
     * @param class-string<CrudControllerInterface> $crudControllerFqcn
     */
    private function transformCrudControllerNameToSnakeCase(string $crudControllerFqcn): string
    {
        $shortName = str_replace(['CrudController', 'Controller'], '', (new \ReflectionClass($crudControllerFqcn))->getShortName());

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }

    /**
     * @param Route[] $adminRoutes
     */
    private function saveAdminRoutesInCache(array $adminRoutes): void
    {
        // to speedup the look up of routes in different parts of the bundle,
        // we cache the admin routes in two different maps:
        // 1) $cache[route_name] => [dashboard, CRUD controller, action]
        // 2) $cache[dashboard][CRUD controller][action] => route_name
        $routeNameToFqcn = [];
        $fqcnToRouteName = [];

        // first, add the routes of all the application dashboards; this is needed because in
        // applications with multiple dashboards, EasyAdmin must be able to find the route data associated
        // to each dashboard; otherwise, the URLs of the menu items when visiting the dashboard route will be wrong
        // TODO: remove this in EasyAdmin 5.0.0, when all the dashboard routes are created using the #[AdminDashboard] attribute;
        // there's no need to remove it now, because when using the #[AdminDashboard] attribute, the admin route created here
        // will be immediately overwritten below by the one created using the attribute
        foreach ($this->getDashboardsRouteConfig() as $dashboardFqcn => $dashboardRouteConfig) {
            $routeNameToFqcn[$dashboardRouteConfig['routeName']] = [
                EA::DASHBOARD_CONTROLLER_FQCN => $dashboardFqcn,
                EA::CRUD_CONTROLLER_FQCN => null,
                EA::CRUD_ACTION => null,
            ];
            $fqcnToRouteName[$dashboardFqcn][''][''] = $dashboardRouteConfig['routeName'];
        }

        // then, add all the generated admin routes
        foreach ($adminRoutes as $routeName => $route) {
            $routeNameToFqcn[$routeName] = [
                EA::DASHBOARD_CONTROLLER_FQCN => $route->getDefault(EA::DASHBOARD_CONTROLLER_FQCN),
                EA::CRUD_CONTROLLER_FQCN => $route->getDefault(EA::CRUD_CONTROLLER_FQCN),
                EA::CRUD_ACTION => $route->getDefault(EA::CRUD_ACTION),
            ];

            $fqcnToRouteName[$route->getDefault(EA::DASHBOARD_CONTROLLER_FQCN)][$route->getDefault(EA::CRUD_CONTROLLER_FQCN) ?? ''][$route->getDefault(EA::CRUD_ACTION) ?? ''] = $routeName;
        }

        $routeNameToFqcnItem = $this->cache->getItem(self::CACHE_KEY_ROUTE_TO_FQCN);
        $routeNameToFqcnItem->set($routeNameToFqcn);
        $this->cache->save($routeNameToFqcnItem);

        $fqcnToRouteNameItem = $this->cache->getItem(self::CACHE_KEY_FQCN_TO_ROUTE);
        $fqcnToRouteNameItem->set($fqcnToRouteName);
        $this->cache->save($fqcnToRouteNameItem);
    }
}
