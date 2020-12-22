<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\UrlSigner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Twig\Environment;

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
    private $adminContextFactory;
    private $dashboardControllerRegistry;
    private $crudControllerRegistry;
    private $controllerFactory;
    private $controllerResolver;
    private $urlGenerator;
    private $requestMatcher;
    private $twig;
    private $urlSigner;

    public function __construct(AdminContextFactory $adminContextFactory, DashboardControllerRegistry $dashboardControllerRegistry, CrudControllerRegistry $crudControllerRegistry, ControllerFactory $controllerFactory, ControllerResolverInterface $controllerResolver, UrlGeneratorInterface $urlGenerator, RequestMatcherInterface $requestMatcher, Environment $twig, UrlSigner $urlSigner)
    {
        $this->adminContextFactory = $adminContextFactory;
        $this->dashboardControllerRegistry = $dashboardControllerRegistry;
        $this->crudControllerRegistry = $crudControllerRegistry;
        $this->controllerFactory = $controllerFactory;
        $this->controllerResolver = $controllerResolver;
        $this->urlGenerator = $urlGenerator;
        $this->requestMatcher = $requestMatcher;
        $this->twig = $twig;
        $this->urlSigner = $urlSigner;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [
                ['handleLegacyEaContext', 10],
                ['onKernelRequest', 0],
            ],
            // the priority must be higher than 0 to run it before ParamConverterListener
            ControllerEvent::class => ['onKernelController', 128],
        ];
    }

    /**
     * It adds support to legacy EasyAdmin requests that include the EA::CONTEXT_NAME query
     * parameter. It creates the new equivalent URL and redirects to it transparently.
     */
    public function handleLegacyEaContext(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (null === $eaContext = $request->query->get(EA::CONTEXT_NAME)) {
            return;
        }

        trigger_deprecation('easycorp/easyadmin-bundle', '3.2.0', 'The "%s" query parameter is deprecated and you no longer need to add it when using custom actions inside EasyAdmin. Read the UPGRADE guide at https://github.com/EasyCorp/EasyAdminBundle/blob/master/UPGRADE.md.', EA::CONTEXT_NAME);

        if (null === $dashboardControllerFqcn = $this->dashboardControllerRegistry->getControllerFqcnByContextId($eaContext)) {
            return;
        }

        $dashboardControllerRoute = $this->dashboardControllerRegistry->getRouteByControllerFqcn($dashboardControllerFqcn);
        $request->query->remove(EA::CONTEXT_NAME);
        $request->query->set(EA::ROUTE_NAME, $request->attributes->get('_route'));
        $request->query->set(EA::ROUTE_PARAMS, $request->attributes->all()['_route_params'] ?? []);
        $newUrl = $this->urlGenerator->generate($dashboardControllerRoute, $request->query->all());

        $dashboardControllerInstance = $this->getDashboardControllerInstance($dashboardControllerFqcn, $request);
        $adminContext = $this->adminContextFactory->create($request, $dashboardControllerInstance, null);
        if ($adminContext->getSignedUrls()) {
            $newUrl = $this->urlSigner->sign($newUrl);
        }

        $event->setResponse(new RedirectResponse($newUrl));
    }

    /**
     * If this is an EasyAdmin request, it creates the AdminContext variable, stores it
     * in the Request as an attribute and injects it as a global Twig variable.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (null === $dashboardControllerFqcn = $this->getDashboardControllerFqcn($request)) {
            return;
        }

        if (null === $dashboardControllerInstance = $this->getDashboardControllerInstance($dashboardControllerFqcn, $request)) {
            return;
        }

        // creating the context is expensive, so it's created once and stored in the request
        // if the current request already has an AdminContext object, do nothing
        if (null === $adminContext = $request->attributes->get(EA::CONTEXT_REQUEST_ATTRIBUTE)) {
            $crudControllerInstance = $this->getCrudControllerInstance($request);
            $adminContext = $this->adminContextFactory->create($request, $dashboardControllerInstance, $crudControllerInstance);
        }

        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $adminContext);

        // this makes the AdminContext available in all templates as a short named variable
        $this->twig->addGlobal('ea', $adminContext);

        if ($adminContext->getSignedUrls() && false === $this->urlSigner->check($request->getUri())) {
            throw new AccessDeniedHttpException('The signature of the URL is not valid.');
        }
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
        if (null === $request->attributes->get(EA::CONTEXT_REQUEST_ATTRIBUTE)) {
            return;
        }

        // if the request is related to a CRUD controller, change the controller to be executed
        if (null !== $crudControllerInstance = $this->getCrudControllerInstance($request)) {
            $symfonyControllerCallable = [$crudControllerInstance, $request->query->get(EA::CRUD_ACTION)];

            // this makes Symfony believe that another controller is being executed
            // (e.g. this is needed for the autowiring of controller action arguments)
            $event->getRequest()->attributes->set('_controller', $symfonyControllerCallable);

            // this actually makes Symfony to execute the other controller
            $event->setController($symfonyControllerCallable);
        }

        // if the request is related to a custom action, change the controller to be executed
        if (null !== $request->query->get(EA::ROUTE_NAME)) {
            $symfonyControllerAsString = $this->getSymfonyControllerFqcn($request);
            if (false !== $symfonyControllerCallable = $this->getSymfonyControllerInstance($symfonyControllerAsString, $request->query->all()[EA::ROUTE_PARAMS] ?? [])) {
                // this makes Symfony believe that another controller is being executed
                // VERY IMPORTANT: here the Symfony controller must be passed as a string ('App\Controller\Foo::index')
                // Otherwise, the param converter of the controller method doesn't work
                $event->getRequest()->attributes->set('_controller', $symfonyControllerAsString);
                // route params must be added as route attribute; otherwise, param converters don't work
                $event->getRequest()->attributes->replace(array_merge(
                    $request->query->all()[EA::ROUTE_PARAMS] ?? [],
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
     */
    private function getDashboardControllerFqcn(Request $request): ?string
    {
        [$controllerFqcn, ] = explode('::', $request->attributes->get('_controller'));

        return is_subclass_of($controllerFqcn, DashboardControllerInterface::class) ? $controllerFqcn : null;
    }

    private function getDashboardControllerInstance(string $dashboardControllerFqcn, Request $request): ?DashboardControllerInterface
    {
        return $this->controllerFactory->getDashboardControllerInstance($dashboardControllerFqcn, $request);
    }

    private function getCrudControllerInstance(Request $request): ?CrudControllerInterface
    {
        if (null !== $crudId = $request->query->get(EA::CRUD_ID)) {
            $crudControllerFqcn = $this->crudControllerRegistry->findCrudFqcnByCrudId($crudId);
        } else {
            $crudControllerFqcn = $request->query->get(EA::CRUD_CONTROLLER_FQCN);
        }

        $crudAction = $request->query->get(EA::CRUD_ACTION);

        return $this->controllerFactory->getCrudControllerInstance($crudControllerFqcn, $crudAction, $request);
    }

    private function getSymfonyControllerFqcn(Request $request): ?string
    {
        $routeName = $request->query->get(EA::ROUTE_NAME);
        $routeParams = $request->query->all()[EA::ROUTE_PARAMS] ?? [];
        $url = $this->urlGenerator->generate($routeName, $routeParams);

        $newRequest = $request->duplicate();
        $newRequest->attributes->remove('_controller');
        $newRequest->attributes->set('_route', $routeName);
        $newRequest->attributes->add($routeParams);
        $newRequest->server->set('REQUEST_URI', $url);

        $parameters = $this->requestMatcher->matchRequest($newRequest);

        return $parameters['_controller'] ?? null;
    }

    /**
     * @return callable|false
     */
    private function getSymfonyControllerInstance(string $controllerFqcn, array $routeParams)
    {
        $newRequest = new Request([], [], ['_controller' => $controllerFqcn, '_route_params' => $routeParams], [], [], []);

        return $this->controllerResolver->getController($newRequest);
    }
}
