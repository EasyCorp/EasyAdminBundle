<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\CacheKey;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminRouteGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class AdminRouteGenerator implements AdminRouteGeneratorInterface
{
    public const BUILT_IN_ACTION_NAMES = [
        Action::INDEX, Action::NEW, Action::EDIT, Action::DETAIL, Action::DELETE,
        Action::BATCH_DELETE, Action::SAVE_AND_ADD_ANOTHER, Action::SAVE_AND_CONTINUE,
        Action::SAVE_AND_RETURN, 'autocomplete', 'renderFilters',
    ];

    private const DEFAULT_ROUTES_CONFIG = [
        Action::INDEX => [
            'actionName' => Action::INDEX,
            'routePath' => '/',
            'routeName' => 'index',
            'methods' => ['GET'],
        ],
        Action::NEW => [
            'actionName' => Action::NEW,
            'routePath' => '/new',
            'routeName' => 'new',
            'methods' => ['GET', 'POST'],
        ],
        Action::BATCH_DELETE => [
            'actionName' => Action::BATCH_DELETE,
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
        Action::EDIT => [
            'actionName' => Action::EDIT,
            'routePath' => '/{entityId}/edit',
            'routeName' => 'edit',
            'methods' => ['GET', 'POST', 'PATCH'],
        ],
        Action::DELETE => [
            'actionName' => Action::DELETE,
            'routePath' => '/{entityId}/delete',
            'routeName' => 'delete',
            'methods' => ['POST'],
        ],
        Action::DETAIL => [
            'actionName' => Action::DETAIL,
            'routePath' => '/{entityId}',
            'routeName' => 'detail',
            'methods' => ['GET'],
        ],
    ];

    /**
     * @param iterable<DashboardControllerInterface> $dashboardControllers
     * @param iterable<CrudControllerInterface>      $crudControllers
     * @param iterable<object>                       $adminRouteControllers Controllers with the #[AdminRoute] attribute
     */
    public function __construct(
        private readonly iterable $dashboardControllers,
        private readonly iterable $crudControllers,
        private readonly CacheItemPoolInterface $cache,
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

        // track controller files as resources so Symfony's routing cache
        // is rebuilt automatically when adding/changing route attributes
        foreach ($this->dashboardControllers as $dashboardController) {
            $collection->addResource(new ReflectionClassResource(new \ReflectionClass($dashboardController)));
        }
        foreach ($this->crudControllers as $crudController) {
            $collection->addResource(new ReflectionClassResource(new \ReflectionClass($crudController)));
        }
        foreach ($this->adminRouteControllers as $adminRouteController) {
            $collection->addResource(new ReflectionClassResource(new \ReflectionClass($adminRouteController)));
        }

        // this dumps all admin routes in a performance-optimized format to later
        // find them quickly without having to use Symfony's router service
        $this->saveAdminRoutesInCache($adminRoutes);
        $this->saveCrudControllerToEntityFqcnMapInCache($this->crudControllers);

        return $collection;
    }

    public function findRouteName(?string $dashboardFqcn = null, ?string $crudControllerFqcn = null, ?string $actionName = null): ?string
    {
        $routeAttributesToRouteName = $this->cache->getItem(CacheKey::ROUTE_ATTRIBUTES_TO_NAME)->get();

        if (null === $dashboardFqcn) {
            $dashboardControllers = iterator_to_array($this->dashboardControllers);
            $dashboardFqcn = $dashboardControllers[array_key_first($dashboardControllers)]::class;
        }

        return $routeAttributesToRouteName[$dashboardFqcn][$crudControllerFqcn ?? ''][$actionName ?? ''] ?? null;
    }

    /**
     * @return array<class-string, string>
     */
    public function getDashboardRoutes(): array
    {
        return $this->cache->getItem(CacheKey::DASHBOARD_FQCN_TO_ROUTE)->get() ?? [];
    }

    /**
     * @return array<string, Route>
     */
    private function generateAdminRoutes(): array
    {
        /** @var array<string, Route> $adminRoutes Stores the collection of admin routes created for the app */
        $adminRoutes = [];
        /** @var array<string, array{type: string, controllerFqcn: string|null, actionName: string|null}> $addedRouteNames Maps each generated route name to the controller/action that created it, to detect and explain duplicated admin routes */
        $addedRouteNames = [];

        // create the routes for the CRUD controllers and actions
        foreach ($this->dashboardControllers as $dashboardController) {
            $dashboardFqcn = $dashboardController::class;
            [$allowedCrudControllers, $deniedCrudControllers] = $this->getAllowedAndDeniedControllers($dashboardFqcn);
            $defaultRoutesConfig = $this->getDefaultRoutesConfig($dashboardFqcn);
            $dashboardRouteConfig = $this->getDashboardsRouteConfig()[$dashboardFqcn];

            // first, create the Symfony route for the dashboards based on its #[AdminDashboard] attribute
            $dashboardRouteName = $dashboardRouteConfig['routeName'];
            $dashboardRoutePath = $dashboardRouteConfig['routePath'];
            $dashboardRouteOptions = $dashboardRouteConfig['routeOptions'];
            $adminRoute = $this->createDashboardRoute($dashboardRoutePath, $dashboardRouteOptions, $dashboardFqcn);
            $adminRoutes[$dashboardRouteName] = $adminRoute;
            $addedRouteNames[$dashboardRouteName] = ['type' => 'dashboard', 'controllerFqcn' => $dashboardFqcn, 'actionName' => null];

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

                // by default, the 'detail' route uses a catch-all route pattern (/{entityId} or its /{id} alias);
                // so, if the user hasn't customized the 'detail' route path, we need to sort the actions
                // to make sure that the 'detail' action is always the last one. The path is canonicalized via a
                // temporary Route so mapped placeholders (e.g. /{id:product.id}) are also detected as catch-all.
                $detailRoutePath = $actionsRouteConfig['detail']['routePath'] ?? null;
                if (null !== $detailRoutePath && \in_array((new Route($detailRoutePath))->getPath(), ['/{entityId}', '/{id}'], true)) {
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

                    if (isset($addedRouteNames[$adminRouteName])) {
                        throw new \RuntimeException($this->getDuplicatedRouteNameErrorMessage($adminRouteName, $addedRouteNames[$adminRouteName], $crudControllerFqcn, $actionRouteConfig['actionName']));
                    }

                    $defaults = [
                        '_controller' => $crudControllerFqcn.'::'.$actionRouteConfig['actionName'],
                        EA::ROUTE_CREATED_BY_EASYADMIN => true,
                        EA::DASHBOARD_CONTROLLER_FQCN => $dashboardFqcn,
                        EA::CRUD_CONTROLLER_FQCN => $crudControllerFqcn,
                        EA::CRUD_ACTION => $actionRouteConfig['actionName'],
                    ];

                    $adminRoute = new Route($adminRoutePath, defaults: $defaults, methods: $actionRouteConfig['methods']);
                    $adminRoutes[$adminRouteName] = $adminRoute;
                    $addedRouteNames[$adminRouteName] = ['type' => 'crud', 'controllerFqcn' => $crudControllerFqcn, 'actionName' => $actionRouteConfig['actionName']];
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

                            if (isset($addedRouteNames[$adminRouteName])) {
                                throw new \RuntimeException(sprintf('The #[AdminRoute] attribute applied to the "%s" controller would create an admin route called "%s", which already exists. You must change the "routeName" argument to generate a different route name.', $controllerFqcn, $adminRouteName));
                            }

                            $adminRoute = $this->createRouteForAdminAttribute($currentClassAdminRoute, $adminRoutePath, $dashboardFqcn, $controllerFqcn, '__invoke');

                            $adminRoutes[$adminRouteName] = $adminRoute;
                            $addedRouteNames[$adminRouteName] = ['type' => 'admin_route', 'controllerFqcn' => $controllerFqcn, 'actionName' => '__invoke'];
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

                        if (isset($addedRouteNames[$adminRouteName])) {
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
                        $addedRouteNames[$adminRouteName] = ['type' => 'admin_route', 'controllerFqcn' => $controllerFqcn, 'actionName' => $method->name];
                    }
                }
            }
        }

        return $adminRoutes;
    }

    /**
     * Builds an accurate, actionable error message when two different controllers/actions
     * generate the same admin route name (Symfony requires route names to be unique).
     *
     * @param array{type: string, controllerFqcn: string|null, actionName: string|null} $existingRoute the controller/action that first generated $routeName
     */
    private function getDuplicatedRouteNameErrorMessage(string $routeName, array $existingRoute, string $crudControllerFqcn, string $actionName): string
    {
        // the route name collided with a non-CRUD route (a dashboard or an #[AdminRoute] controller)
        if ('crud' !== $existingRoute['type'] || null === $existingRoute['controllerFqcn']) {
            return sprintf('The "%s" CRUD controller generates a route named "%s" for its "%s()" action, but that route name is already used by another route in your application. Add the #[AdminRoute(name: \'...\')] attribute to this controller or action to generate a unique route name.', $crudControllerFqcn, $routeName, $actionName);
        }

        $existingControllerFqcn = $existingRoute['controllerFqcn'];
        $existingShortName = (new \ReflectionClass($existingControllerFqcn))->getShortName();
        $currentShortName = (new \ReflectionClass($crudControllerFqcn))->getShortName();

        // two different CRUD controllers that share the same short class name (in different namespaces)
        if ($existingShortName === $currentShortName) {
            return sprintf('Your application has at least two different CRUD controllers with the same "%s" class name (in different namespaces): "%s" and "%s". Both generate the same route name "%s", but EasyAdmin needs unique class names to generate unique route names. Rename one of these controllers to resolve the issue.', $currentShortName, $existingControllerFqcn, $crudControllerFqcn, $routeName);
        }

        // two different CRUD controllers whose name + action combination collides; this typically
        // happens when one entity/controller name is a prefix of another (e.g. "Foo" and "FooBatch")
        return sprintf('The route name "%s" is generated by two different CRUD actions: "%s::%s()" and "%s::%s()". This usually happens when one entity/controller name is a prefix of another (e.g. "Foo" and "FooBatch"). Add the #[AdminRoute(name: \'...\')] attribute to one of these controllers or actions to generate a unique route name.', $routeName, $existingControllerFqcn, $existingRoute['actionName'], $crudControllerFqcn, $actionName);
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

            if (isset($customRouteConfig['routePath']) && \in_array($action, [Action::EDIT, Action::DETAIL, Action::DELETE], true) && null === $this->getEntityIdPlaceholderName($customRouteConfig['routePath'])) {
                throw new \RuntimeException(sprintf('In the #[AdminDashboard] attribute of the "%s" dashboard controller, the path for the "%s" action must contain the "{id}" or "{entityId}" placeholder.', $dashboardFqcn, $action));
            }
        }

        return array_replace_recursive(self::DEFAULT_ROUTES_CONFIG, $customRoutesConfig);
    }

    /**
     * @return array<class-string, array{routeName: string, routePath: string, routeOptions: array<string, mixed>}>
     */
    private function getDashboardsRouteConfig(): array
    {
        $config = [];

        foreach ($this->dashboardControllers as $dashboardController) {
            $reflectionClass = new \ReflectionClass($dashboardController);
            $attributes = $reflectionClass->getAttributes(AdminDashboard::class);

            if ([] === $attributes) {
                throw new \RuntimeException(sprintf('The "%s" dashboard controller must apply the #[AdminDashboard] attribute to define the route path and route name of the dashboard (e.g. #[AdminDashboard(routePath: \'/admin\', routeName: \'admin\')]). Using the default #[Route] attribute from Symfony on the "index()" method of the dashboard does no longer work.', $reflectionClass->getName()));
            }

            $adminDashboardAttribute = $attributes[0]->newInstance();
            $routeName = $adminDashboardAttribute->routeName;
            $routePath = $adminDashboardAttribute->routePath;
            $routeOptions = $adminDashboardAttribute->routeOptions;
            if (null !== $routePath) {
                $routePath = rtrim($adminDashboardAttribute->routePath, '/');
            }

            if (null === $routeName || null === $routePath) {
                throw new \RuntimeException(sprintf('The "%s" dashboard controller applies the #[AdminDashboard] attribute but it\'s missing either the "routePath" or "routeName" arguments or both. Check that you define both to properly configure the main route of your dashboard (e.g. #[AdminDashboard(routePath: \'/admin\', routeName: \'admin\')]). Using the default #[Route] attribute from Symfony on the "index()" method of the dashboard does no longer work.', $reflectionClass->getName()));
            }

            $config[$reflectionClass->getName()] = [
                'routeName' => $routeName,
                'routePath' => $routePath,
                'routeOptions' => $routeOptions,
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

            $adminRouteAttributes = $method->getAttributes(AdminRoute::class);
            // process each AdminRoute attribute separately to support multiple routes per action
            foreach ($adminRouteAttributes as $index => $adminRouteAttribute) {
                /** @var AdminRoute $adminRouteInstance */
                $adminRouteInstance = $adminRouteAttribute->newInstance();

                // each route can define more than one route, so we cannot use the action name as the array key
                $routeId = $action.'_route_'.++$index;

                if (null !== $adminRouteInstance->path) {
                    if (\in_array($action, [Action::EDIT, Action::DETAIL, Action::DELETE], true) && null === $this->getEntityIdPlaceholderName($adminRouteInstance->path)) {
                        throw new \RuntimeException(sprintf('In the "%s" CRUD controller, the #[AdminRoute] attribute applied to the "%s()" action is missing the "{id}" or "{entityId}" placeholder in its route path.', $crudControllerFqcn, $action));
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
                    if (\in_array($action, [Action::INDEX, Action::DETAIL], true)) {
                        $methods = ['GET'];
                    } elseif (\in_array($action, [Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE], true)) {
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
        }

        return $customActionsConfig;
    }

    /**
     * Returns the name of the placeholder used in the given route path to identify the
     * current entity: 'entityId' (canonical) or its 'id' alias; null if neither is present.
     *
     * It matches both the bare placeholders ({id}, {entityId}) and their mapped/constrained
     * forms ({id:product.id}, {entityId<\d+>}, etc.) while ignoring unrelated variables such
     * as {userId} or {idCard}.
     */
    private function getEntityIdPlaceholderName(string $routePath): ?string
    {
        if (1 === preg_match('#\{!?(id|entityId)(:[\w\x80-\xFF]++(\.[\w\x80-\xFF]++)?)?(<.*?>)?(\?[^}]*+)?\}#', $routePath, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param array<string, mixed>                       $routeOptions
     * @param class-string<DashboardControllerInterface> $dashboardFqcn
     */
    private function createDashboardRoute(string $routePath, array $routeOptions, string $dashboardFqcn): Route
    {
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

        return $route;
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
        $cleanShortName = preg_replace('/(?:Crud)?Controller$/', '', (new \ReflectionClass($crudControllerFqcn))->getShortName());
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
        $shortName = preg_replace('/(?:Crud)?Controller$/', '', (new \ReflectionClass($crudControllerFqcn))->getShortName());

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }

    /**
     * @param Route[] $adminRoutes
     */
    private function saveAdminRoutesInCache(array $adminRoutes): void
    {
        // to speedup the look up of routes in different parts of the bundle,
        // we cache the admin routes in two different maps:
        // 1) Routes related to dashboard controllers only
        // 2) All admin routes (including the dashboard controller routes)
        //
        // for each cache, we store the data in the following maps to optimize lookups:
        // 1) for Dashboard routes:
        //    $cache[dashboard fqcn] => dashboard_route_name
        // 2) for all admin routes:
        //    2.1) $cache[route_name] => [dashboard fqcn, CRUD controller fqcn, action]
        //    2.2) $cache[dashboard fqcn][CRUD controller fqcn][action] => route_name

        // first, add the routes of all the application dashboards; this is needed because in
        // applications with multiple dashboards, EasyAdmin must be able to find the route data associated
        // to each dashboard; otherwise, the URLs of the menu items when visiting the dashboard route will be wrong
        $dashboardFqcnToRouteName = [];
        foreach ($this->getDashboardsRouteConfig() as $dashboardFqcn => $dashboardRouteConfig) {
            $dashboardFqcnToRouteName[$dashboardFqcn] = $dashboardRouteConfig['routeName'];
        }

        $dashboardFqcnToRouteNameItem = $this->cache->getItem(CacheKey::DASHBOARD_FQCN_TO_ROUTE);
        $dashboardFqcnToRouteNameItem->set($dashboardFqcnToRouteName);
        $this->cache->save($dashboardFqcnToRouteNameItem);

        // then, add all the generated admin routes
        $routeNameToRouteAttributes = [];
        $routeFqcnToRouteName = [];
        foreach ($adminRoutes as $routeName => $route) {
            // store which placeholder (if any) this route uses to identify the current entity,
            // so AdminUrlGenerator knows whether to generate the URL with 'entityId' or its 'id' alias.
            // the canonical compiled path variables are used (instead of parsing the raw path) to ignore
            // any literal '{id}' that might appear inside route defaults or requirements
            $pathVariables = $route->compile()->getPathVariables();
            $entityIdParamName = \in_array(EA::ENTITY_ID, $pathVariables, true) ? EA::ENTITY_ID
                : (\in_array('id', $pathVariables, true) ? 'id' : null);

            $routeNameToRouteAttributes[$routeName] = [
                EA::DASHBOARD_CONTROLLER_FQCN => $route->getDefault(EA::DASHBOARD_CONTROLLER_FQCN),
                EA::CRUD_CONTROLLER_FQCN => $route->getDefault(EA::CRUD_CONTROLLER_FQCN),
                EA::CRUD_ACTION => $route->getDefault(EA::CRUD_ACTION),
                'entityIdParamName' => $entityIdParamName,
            ];

            $routeFqcnToRouteName[$route->getDefault(EA::DASHBOARD_CONTROLLER_FQCN)][$route->getDefault(EA::CRUD_CONTROLLER_FQCN) ?? ''][$route->getDefault(EA::CRUD_ACTION) ?? ''] = $routeName;
        }

        $routeNameToFqcnItem = $this->cache->getItem(CacheKey::ROUTE_NAME_TO_ATTRIBUTES);
        $routeNameToFqcnItem->set($routeNameToRouteAttributes);
        $this->cache->save($routeNameToFqcnItem);

        $fqcnToRouteNameItem = $this->cache->getItem(CacheKey::ROUTE_ATTRIBUTES_TO_NAME);
        $fqcnToRouteNameItem->set($routeFqcnToRouteName);
        $this->cache->save($fqcnToRouteNameItem);
    }

    // This replaces the ControllerRegistry that existed in previous EasyAdmin versions.
    // It stores the map between CRUD controllers and their associated entity FQCN:
    //   $cache['crud_controller_fqcn'] => 'entity_fqcn'
    // (AdminControllerRegistry derives the reverse entity-to-controller map from it)
    /**
     * @param iterable<CrudControllerInterface> $crudControllers
     */
    private function saveCrudControllerToEntityFqcnMapInCache(iterable $crudControllers): void
    {
        $crudToEntityMap = [];
        foreach ($crudControllers as $crudController) {
            $crudToEntityMap[$crudController::class] = $crudController::getEntityFqcn();
        }

        $crudToEntityCacheItem = $this->cache->getItem(CacheKey::CRUD_FQCN_TO_ENTITY_FQCN);
        $crudToEntityCacheItem->set($crudToEntityMap);
        $this->cache->save($crudToEntityCacheItem);
    }
}
