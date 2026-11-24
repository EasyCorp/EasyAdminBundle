<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminRouteGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionGroupDto;
use EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\AccessMapInterface;

/**
 * This subscriber acts as a "proxy" of all backend requests. First, if the
 * request is related to EasyAdmin, it creates the AdminContext variable and
 * stores it in the Request as an attribute.
 *
 * Second, it uses Symfony events to serve all backend requests using a single
 * route. The trick is to change dynamically the controller to execute when
 * the request is related to a CRUD action or a normal Symfony route/action.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class AdminRouterSubscriber implements EventSubscriberInterface
{
    private bool $requestAlreadyProcessedAsPrettyUrl = false;

    public function __construct(
        private readonly AdminContextFactory $adminContextFactory,
        private readonly ControllerFactory $controllerFactory,
        private readonly ControllerResolverInterface $controllerResolver,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestMatcherInterface $requestMatcher,
        private readonly CacheItemPoolInterface $cache,
        private readonly AdminRouteGeneratorInterface $adminRouteGenerator,
        private readonly string $buildDir,
        private readonly AdminControllerRegistryInterface $adminControllers,
        private readonly ?AccessMapInterface $accessMap = null,
        private readonly ?AuthorizationCheckerInterface $authorizationChecker = null,
        private readonly ?AccessDecisionManagerInterface $accessDecisionManager = null,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [
                ['onKernelRequestPrettyUrls', 1],
                ['onKernelRequest', 0],
            ],
            // the priority must be higher than 0 to run it before ParamConverterListener
            ControllerEvent::class => ['onKernelController', 128],
        ];
    }

    public function onKernelRequestPrettyUrls(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (false === $request->attributes->getBoolean(EA::ROUTE_CREATED_BY_EASYADMIN)) {
            // at this point, the incoming request can be:
            //   (1) A dashboard URL (they don't have the EasyAdmin route attributes) of an app using pretty URLs
            //   (2) An ugly URL from EasyAdmin (they all use the main dashboard URL and select the action to execute via query params)
            //   (3) a regular Symfony request (not related to EasyAdmin)

            // check if the URL includes the 'crudControllerFqcn' query parameter
            // if it does, this is case (2) and we don't handle it as a pretty URL
            if ($request->query->has(EA::CRUD_CONTROLLER_FQCN)) {
                return;
            }

            // this can be case (1) or (3). Sadly, the dashboard routes don't include
            // the EasyAdmin route attributes because they are regular Symfony routes.
            // I can't find any way (inside our custom route loader, in a compiler pass, etc.) to add the
            // custom EasyAdmin route defaults/attributes to other existing Symfony routes. So, we have to
            // check if the route of the current request matches any of the cached dashboard routes
            $dashboardRoutesCachePath = $this->buildDir.'/'.CacheWarmer::DASHBOARD_ROUTES_CACHE;
            $dashboardControllerRoutes = [];
            if (file_exists($dashboardRoutesCachePath)) {
                try {
                    $dashboardControllerRoutes = require $dashboardRoutesCachePath;
                } catch (\Throwable) {
                }
            }

            // this is not a cached dashboard route, so this is case (3) a regular Symfony request
            if (!\array_key_exists($request->attributes->get('_route', ''), $dashboardControllerRoutes)) {
                return;
            }
        }

        // legacy URLs select the CRUD controller and action with query parameters, which is what
        // onKernelController() runs; onKernelRequest() builds the admin context of that CRUD
        // controller and action (here it would be built without any CRUD controller) and
        // redirects to the equivalent pretty URL when it exists
        if (null === $request->attributes->get(EA::CRUD_CONTROLLER_FQCN) && $request->query->has(EA::CRUD_CONTROLLER_FQCN)) {
            return;
        }

        // edge-case: in some scenarios, admin routes are generated by the custom route loader
        // and their information is cached but then removed from the cache (e.g. when running
        // 'rm -fr var/cache/* && bin/console cache:clear'). If that's the case, regenerate the
        // admin routes (only if the app uses pretty URLs) to force saving them in the cache again.
        // see https://github.com/EasyCorp/EasyAdminBundle/issues/6680
        $adminRoutes = $this->cache->getItem(AdminRouteGenerator::CACHE_KEY_ROUTE_TO_FQCN)->get();
        if (null === $adminRoutes && $this->adminRouteGenerator->usesPrettyUrls()) {
            $this->adminRouteGenerator->generateAll();
        }

        if (null !== $redirectResponse = $this->getRedirectionToAdminRoute($request)) {
            $event->setResponse($redirectResponse);

            return;
        }

        $dashboardControllerFqcn = $request->attributes->get(EA::DASHBOARD_CONTROLLER_FQCN);
        if (null === $dashboardControllerFqcn) {
            if (!isset($dashboardControllerRoutes[$request->attributes->get('_route')])) {
                return;
            }

            $dashboardCallableAsString = $dashboardControllerRoutes[$request->attributes->get('_route')];
            [$dashboardControllerFqcn, ] = explode('::', $dashboardCallableAsString);
        }

        if (null === $dashboardControllerInstance = $this->getDashboardControllerInstance($dashboardControllerFqcn, $request)) {
            return;
        }

        // creating the context is expensive, so it's created once and stored in the request
        // if the current request already has an AdminContext object, do nothing
        if (null === $adminContext = $request->attributes->get(EA::CONTEXT_REQUEST_ATTRIBUTE)) {
            $crudControllerFqcn = $request->attributes->get(EA::CRUD_CONTROLLER_FQCN);
            $actionName = $request->attributes->get(EA::CRUD_ACTION);

            $crudControllerInstance = $this->controllerFactory->getCrudControllerInstance($crudControllerFqcn, $actionName, $request);

            try {
                $adminContext = $this->adminContextFactory->create($request, $dashboardControllerInstance, $crudControllerInstance, $actionName);
            } catch (BaseException $e) {
                // if context creation fails (e.g. entity not found), create a context
                // without the entity so the ExceptionListener can still render the
                // EasyAdmin error page with the proper layout
                $entityId = $request->attributes->get(EA::ENTITY_ID) ?? $request->query->get(EA::ENTITY_ID);
                $request->attributes->remove(EA::ENTITY_ID);
                $request->query->remove(EA::ENTITY_ID);

                $adminContext = $this->adminContextFactory->create($request, $dashboardControllerInstance, $crudControllerInstance, $actionName);
                $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $adminContext);
                $this->requestAlreadyProcessedAsPrettyUrl = true;

                // restore the entity ID so the exception message is accurate
                $request->attributes->set(EA::ENTITY_ID, $entityId);

                throw $e;
            }
        }

        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $adminContext);
        $this->requestAlreadyProcessedAsPrettyUrl = true;

        $this->assertCustomCrudActionIsGranted($adminContext, $request);
    }

    /**
     * If this is an EasyAdmin request, it creates the AdminContext variable, stores it
     * in the Request as an attribute and injects it as a global Twig variable.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->requestAlreadyProcessedAsPrettyUrl) {
            return;
        }

        // return early if this is not a URL associated with EasyAdmin
        $request = $event->getRequest();
        if (null === $dashboardControllerFqcn = $this->getDashboardControllerFqcn($request)) {
            return;
        }

        // if this is a ugly URL from legacy EasyAdmin versions and the application
        // uses pretty URLs, redirect to the equivalent pretty URL
        if ($this->adminRouteGenerator->usesPrettyUrls() && null !== $entityFqcnOrCrudControllerFqcn = $request->query->get(EA::CRUD_CONTROLLER_FQCN)) {
            if (is_subclass_of($entityFqcnOrCrudControllerFqcn, CrudControllerInterface::class)) {
                $crudControllerFqcn = $entityFqcnOrCrudControllerFqcn;
            } else {
                $crudControllerFqcn = $this->adminControllers->findCrudControllerByEntity($entityFqcnOrCrudControllerFqcn);
            }

            $prettyUrlRoute = $this->adminRouteGenerator->findRouteName($dashboardControllerFqcn, $crudControllerFqcn, $request->query->get(EA::CRUD_ACTION, ''));
            if (null !== $prettyUrlRoute) {
                $request->query->remove(EA::CRUD_CONTROLLER_FQCN);

                // the path of the dashboard can have parameters, which the pretty URL needs too
                $routeParams = array_merge($request->query->all(), $request->attributes->get('_route_params', []));
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate($prettyUrlRoute, $routeParams)));

                return;
            }
        }

        if (null !== $redirectResponse = $this->getRedirectionToAdminRoute($request)) {
            $event->setResponse($redirectResponse);

            return;
        }

        if (null === $dashboardControllerInstance = $this->getDashboardControllerInstance($dashboardControllerFqcn, $request)) {
            return;
        }

        // creating the context is expensive, so it's created once and stored in the request
        // if the current request already has an AdminContext object, do nothing
        if (null === $adminContext = $request->attributes->get(EA::CONTEXT_REQUEST_ATTRIBUTE)) {
            $crudControllerInstance = $this->getCrudControllerInstance($request, $dashboardControllerFqcn);
            $adminContext = $this->adminContextFactory->create($request, $dashboardControllerInstance, $crudControllerInstance);
        }

        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $adminContext);

        $this->assertCustomCrudActionIsGranted($adminContext, $request);
    }

    /**
     * In EasyAdmin all backend requests are served via the same route (that allows to
     * detect under which dashboard you want to process the request). This method handles
     * the requests related to "CRUD controller actions" and "custom Symfony actions".
     * The trick used is to change dynamically the controller executed by Symfony.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        if (null === $adminContext = $request->attributes->get(EA::CONTEXT_REQUEST_ATTRIBUTE)) {
            return;
        }

        // when a request doesn't meet the scheme or the trailing slash of the matched route, the Symfony router
        // keeps the route attributes but replaces the controller with a redirection; that redirection must
        // run instead of the controller swapped below, or the restrictions of the route (e.g. 'https') are bypassed
        $controller = $event->getController();
        $controllerObject = \is_array($controller) ? $controller[0] : $controller;
        if (!$controllerObject instanceof DashboardControllerInterface && !$controllerObject instanceof CrudControllerInterface) {
            return;
        }

        // if the request is related to a CRUD controller, change the controller to be executed
        if (null !== $crudControllerInstance = $this->getCrudControllerInstance($request, $adminContext->getDashboardControllerFqcn())) {
            $symfonyControllerFqcnCallable = [$crudControllerInstance, $request->attributes->get(EA::CRUD_ACTION) ?? $request->query->get(EA::CRUD_ACTION)];
            $symfonyControllerStringCallable = [$crudControllerInstance::class, $request->attributes->get(EA::CRUD_ACTION) ?? $request->query->get(EA::CRUD_ACTION)];

            // this makes Symfony believe that another controller is being executed
            // (e.g. this is needed for the autowiring of controller action arguments)
            // VERY IMPORTANT: here the Symfony controller must be passed as a string (['App\Controller\Foo', 'index'])
            // Otherwise, the param converter of the controller method doesn't work
            $event->getRequest()->attributes->set('_controller', $symfonyControllerStringCallable);

            // this actually makes Symfony to execute the other controller
            $event->setController($symfonyControllerFqcnCallable);
        }

        // if the request is related to a custom action, change the controller to be executed
        if (null !== $request->query->get(EA::ROUTE_NAME)) {
            $targetRouteParameters = $this->getTargetRouteParameters($request);
            $symfonyControllerAsString = $targetRouteParameters['_controller'] ?? null;
            $targetRouteAttributes = array_diff_key($targetRouteParameters, ['_route' => true, '_controller' => true]);
            $symfonyControllerCallable = $this->getSymfonyControllerInstance($symfonyControllerAsString, $targetRouteAttributes);
            if (false !== $symfonyControllerCallable) {
                // this makes Symfony believe that another controller is being executed
                // (e.g. this is needed for the autowiring of controller action arguments)
                // VERY IMPORTANT: here the Symfony controller must be passed as a string ('App\Controller\Foo::index')
                // Otherwise, the param converter of the controller method doesn't work
                $event->getRequest()->attributes->set('_controller', $symfonyControllerAsString);
                // route params must be added as route attribute; otherwise, param converters don't work.
                // They are the ones matched by the router, like on a direct request, because the
                // 'routeParams' of the query string can't decide the arguments of the target controller
                $event->getRequest()->attributes->replace(array_merge(
                    $targetRouteAttributes,
                    $event->getRequest()->attributes->all()
                ));

                // this actually makes Symfony to execute the other controller
                $event->setController($symfonyControllerCallable);
            }
        }
    }

    /**
     * It returns the FQCN of the EasyAdmin Dashboard controller used to serve this
     * request or null if this is not an EasyAdmin request.
     * Because of how EasyAdmin works, all backend requests are handled via the
     * Dashboard controller, so its enough to check if the request controller implements
     * the DashboardControllerInterface.
     *
     * @return class-string<DashboardControllerInterface>|null
     */
    private function getDashboardControllerFqcn(Request $request): ?string
    {
        $controller = $request->attributes->get(EA::DASHBOARD_CONTROLLER_FQCN) ?? $request->attributes->get('_controller');
        $controllerFqcn = null;

        if (\is_string($controller)) {
            [$controllerFqcn, ] = explode('::', $controller);
        }

        if (\is_array($controller) && isset($controller[0]) && \is_string($controller[0])) {
            $controllerFqcn = $controller[0];
        }

        if (\is_object($controller)) {
            $controllerFqcn = $controller::class;
        }

        return is_subclass_of($controllerFqcn, DashboardControllerInterface::class) ? $controllerFqcn : null;
    }

    private function getDashboardControllerInstance(string $dashboardControllerFqcn, Request $request): ?DashboardControllerInterface
    {
        return $this->controllerFactory->getDashboardControllerInstance($dashboardControllerFqcn, $request);
    }

    private function getCrudControllerInstance(Request $request, string $dashboardControllerFqcn): ?CrudControllerInterface
    {
        // when using pretty URLs, the data is in the request attributes instead of the query string
        $crudControllerFqcn = $request->attributes->get(EA::CRUD_CONTROLLER_FQCN) ?? $request->query->get(EA::CRUD_CONTROLLER_FQCN);
        $crudAction = $request->attributes->get(EA::CRUD_ACTION) ?? $request->query->get(EA::CRUD_ACTION);

        $crudControllerInstance = $this->controllerFactory->getCrudControllerInstance($crudControllerFqcn, $crudAction, $request);

        // the restrictions of the dashboard and of the #[AdminRoute] attributes are applied when generating the
        // admin routes, so a CRUD controller taken from the query string must pass them too; otherwise, any
        // CRUD controller would be reachable under any dashboard
        if (null !== $crudControllerInstance
            && null === $request->attributes->get(EA::CRUD_CONTROLLER_FQCN)
            && $this->adminRouteGenerator instanceof AdminRouteGenerator
            && $this->adminRouteGenerator->usesPrettyUrls()
            && !$this->adminRouteGenerator->isCrudActionAllowedInDashboard($dashboardControllerFqcn, $crudControllerInstance::class, (string) $crudAction)
        ) {
            throw new NotFoundHttpException(sprintf('The "%s::%s()" action of the CRUD controller is not available in the "%s" dashboard.', $crudControllerInstance::class, $crudAction, $dashboardControllerFqcn));
        }

        return $crudControllerInstance;
    }

    /**
     * EasyAdmin always links admin routes through their own URLs (see AdminUrlGenerator::generateUrl()).
     * Forwarding one of them through '?routeName=' would run its controller with the admin context of
     * the current URL, so its action and entity permissions would be checked against the configuration
     * of the wrong CRUD controller (or of no CRUD controller at all). Redirect to the real URL instead,
     * which builds the right admin context; the 307 status code keeps the HTTP method and the body.
     *
     * The target is recognized by the route it resolves to, not by the given name, because that name
     * can be an alias of an admin route.
     */
    private function getRedirectionToAdminRoute(Request $request): ?RedirectResponse
    {
        if (null === $request->query->get(EA::ROUTE_NAME)) {
            return null;
        }

        try {
            $targetRoute = $this->resolveTargetRoute($request);
        } catch (RoutingExceptionInterface) {
            // the same error is thrown later when forwarding the request, once the
            // admin context exists, so the error page uses the EasyAdmin layout
            return null;
        }

        if (true !== ($targetRoute['parameters'][EA::ROUTE_CREATED_BY_EASYADMIN] ?? false)) {
            return null;
        }

        return new RedirectResponse($targetRoute['url'], Response::HTTP_TEMPORARY_REDIRECT);
    }

    /**
     * @return array<string, mixed>
     */
    private function getTargetRouteParameters(Request $request): array
    {
        $targetRoute = $this->resolveTargetRoute($request);
        $this->assertTargetRouteAccessIsGranted($targetRoute['request']);

        return $targetRoute['parameters'];
    }

    /**
     * @return array{url: string, request: Request, parameters: array<string, mixed>}
     */
    private function resolveTargetRoute(Request $request): array
    {
        $routeName = $request->query->get(EA::ROUTE_NAME);
        $routeParams = $request->query->all(EA::ROUTE_PARAMS);
        $url = $this->urlGenerator->generate($routeName, $routeParams, UrlGeneratorInterface::ABSOLUTE_PATH);

        $newRequest = $request->duplicate();
        $newRequest->attributes->remove('_controller');
        $newRequest->attributes->set('_route', $routeName);

        // If the application is running behind a proxy under a permanent
        // subpath prefix (AKA sub-folder, sub-url or sub-path) using the HTTP
        // header x-forwarded-prefix, the URL is generated with
        // that prefix. We need to remove it before we can match it.
        $pathToMatch = $url;
        $basePath = $request->getBasePath();
        if ('' !== $request->getBasePath() && str_starts_with($pathToMatch, $basePath)) {
            $pathToMatch = mb_substr($pathToMatch, mb_strlen($basePath));
        }

        $newRequest->server->set('REQUEST_URI', $pathToMatch);

        $parameters = $this->requestMatcher->matchRequest($newRequest);

        // access_control rules can match on the attributes that RouterListener adds to the
        // request ('attributes' and 'route' options), so the target request needs the same
        // ones; '_route' becomes the matched name, not the given one, which can be an alias
        $newRequest->attributes->add($parameters);
        $newRequest->attributes->set('_route_params', array_diff_key($parameters, ['_route' => true, '_controller' => true]));

        return ['url' => $url, 'request' => $newRequest, 'parameters' => $parameters];
    }

    /**
     * The controller of a custom action (?routeName=...) is swapped in on the
     * 'kernel.controller' event, which runs *after* Symfony's security firewall has
     * already evaluated 'access_control' against the original admin URL. Without this
     * check, any path-based access_control rule protecting the *target* route would be
     * silently skipped, allowing it to be reached through EasyAdmin. So we re-evaluate
     * that rule here against the target request.
     *
     * The rule is decided like Symfony's AccessListener does: all its attributes (roles and
     * the allow_if expression) in a single call to the access decision manager, with the
     * target request as the subject. Deciding them one by one would grant access when any
     * of them is granted, which only matches the default 'affirmative' strategy.
     */
    private function assertTargetRouteAccessIsGranted(Request $request): void
    {
        if (null === $this->accessMap) {
            return;
        }

        [$attributes] = $this->accessMap->getPatterns($request);

        if (null === $attributes || [] === $attributes || [AuthenticatedVoter::PUBLIC_ACCESS] === $attributes) {
            return;
        }

        if ($this->isAccessControlRuleGranted($attributes, $request)) {
            return;
        }

        throw new AccessDeniedException(sprintf('Access to the "%s" route is denied by an access control rule; it cannot be reached through an EasyAdmin custom action.', $request->attributes->get('_route')));
    }

    /**
     * @param array<mixed> $attributes
     */
    private function isAccessControlRuleGranted(array $attributes, Request $request): bool
    {
        if (null !== $this->accessDecisionManager && null !== $this->tokenStorage) {
            $token = $this->tokenStorage->getToken() ?? new NullToken();

            // the flag that allows several attributes is not part of AccessDecisionManagerInterface
            // and it moved from the 4th to the 5th argument when Symfony 7.3 added AccessDecision;
            // these are the calls made by the AccessListener of each Symfony version
            if (class_exists(AccessDecision::class)) {
                // @phpstan-ignore arguments.count
                return $this->accessDecisionManager->decide($token, $attributes, $request, new AccessDecision(), true);
            }

            // @phpstan-ignore argument.type
            return $this->accessDecisionManager->decide($token, $attributes, $request, true);
        }

        // without the decision manager the strategy is unknown, so a rule with several
        // attributes can't be decided like Symfony does and access is denied
        if (1 !== \count($attributes) || null === $this->authorizationChecker) {
            return false;
        }

        return $this->authorizationChecker->isGranted(reset($attributes), $request);
    }

    /**
     * Action permissions and disabled actions are keyed by action name, but the request
     * only carries the controller method and the route name. A custom CRUD action method
     * can be reached by several actions (on different pages, or with different names), so
     * the request is allowed when at least one of them could be rendered for this user.
     * Built-in actions are excluded because their controller methods check this themselves.
     */
    private function assertCustomCrudActionIsGranted(AdminContext $adminContext, Request $request): void
    {
        $crudDto = $adminContext->getCrud();
        $crudActionName = $crudDto?->getCurrentAction();
        if (null === $this->authorizationChecker || null === $crudActionName || \in_array($crudActionName, AdminRouteGenerator::BUILT_IN_ACTION_NAMES, true)) {
            return;
        }

        $linkedActions = $this->findActionsLinkedToCrudAction($crudDto->getActionsConfig(), $crudActionName, $request);
        if ([] === $linkedActions) {
            return;
        }

        foreach ($linkedActions as [$actionDto, $isEntityAction]) {
            // use the same subject as when rendering the action (see ActionFactory)
            $entityDto = $isEntityAction ? $adminContext->getEntity() : null;
            if ($this->authorizationChecker->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => $actionDto, 'entity' => $entityDto])) {
                return;
            }
        }

        throw new ForbiddenActionException($adminContext);
    }

    /**
     * The actions inside a group are typed by the group they belong to, not by themselves.
     *
     * @return list<array{ActionDto, bool}>
     */
    private function findActionsLinkedToCrudAction(ActionConfigDto $actionConfigDto, string $crudActionName, Request $request): array
    {
        $actionsPerPage = $actionConfigDto->getActions();
        $actionItems = $actionsPerPage instanceof ActionCollection ? $actionsPerPage->all() : array_merge(...array_values(array_map(array_values(...), $actionsPerPage)));

        $linkedActions = [];
        foreach ($actionItems as $actionItem) {
            $actionDtos = $actionItem instanceof ActionGroupDto ? array_filter([$actionItem->getMainAction(), ...$actionItem->getActions()]) : [$actionItem];
            foreach ($actionDtos as $actionDto) {
                if ($crudActionName === $actionDto->getCrudActionName() || (null !== $actionDto->getRouteName() && $this->isRouteOfCurrentRequest($actionDto->getRouteName(), $request))) {
                    $linkedActions[] = [$actionDto, $actionItem->isEntityAction()];
                }
            }
        }

        return $linkedActions;
    }

    /**
     * The route name of an action can be an alias of the current route, so routes are compared
     * by the URL they generate with the parameters of the current request instead of by name.
     */
    private function isRouteOfCurrentRequest(string $routeName, Request $request): bool
    {
        $currentRouteName = $request->attributes->get('_route');
        if (null === $currentRouteName || $routeName === $currentRouteName) {
            return $routeName === $currentRouteName;
        }

        $currentRouteParams = $request->attributes->get('_route_params', []);

        try {
            return $this->urlGenerator->generate($routeName, $currentRouteParams) === $this->urlGenerator->generate($currentRouteName, $currentRouteParams);
        } catch (RouteNotFoundException|MissingMandatoryParametersException|InvalidParameterException) {
            // the action route doesn't exist or needs other parameters, so it can't be the current route
            return false;
        }
    }

    /**
     * @param array<string, mixed> $routeParams
     */
    private function getSymfonyControllerInstance(string $controllerFqcn, array $routeParams): callable|false
    {
        $newRequest = new Request([], [], ['_controller' => $controllerFqcn, '_route_params' => $routeParams], [], [], []);

        return $this->controllerResolver->getController($newRequest);
    }
}
