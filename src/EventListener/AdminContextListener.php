<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * Initializes the AdminContext variable and stores it as a request attribute.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class AdminContextListener
{
    private $adminContextFactory;
    private $dashboardRegistry;
    private $controllerResolver;
    private $twig;

    public function __construct(AdminContextFactory $adminContextFactory, DashboardControllerRegistry $dashboardRegistry, ControllerResolverInterface $controllerResolver, Environment $twig)
    {
        $this->adminContextFactory = $adminContextFactory;
        $this->dashboardRegistry = $dashboardRegistry;
        $this->controllerResolver = $controllerResolver;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$this->isEasyAdminRequest($event)) {
            return;
        }

        $crudControllerCallable = $this->getCrudController($event->getRequest());
        $crudControllerInstance = null !== $crudControllerCallable ? $crudControllerCallable[0] : null;

        // creating the context is expensive, so it's created once and stored in the request
        // if the current request already has an AdminContext object, do nothing
        if (null === $adminContext = $this->getAdminContext($event)) {
            $dashboardControllerInstance = $this->getDashboardControllerInstance($event);
            $adminContext = $this->createAdminContext($event->getRequest(), $dashboardControllerInstance, $crudControllerInstance);
        }

        $this->setAdminContext($event, $adminContext);

        // this makes the AdminContext available in all templates as a short named variable
        $this->twig->addGlobal('ea', $adminContext);

        // if the request is related to a CRUD controller, change the controller to execute
        if (null !== $crudControllerInstance) {
            $crudControllerClass = \get_class($crudControllerInstance);
            $crudControllerAction = $crudControllerCallable[1];
            $newControllerAsString = sprintf('%s::%s', $crudControllerClass, $crudControllerAction);

            // this makes Symfony believe that another controller is being executed
            // (e.g. this is needed for the autowiring of controller action arguments)
            $event->getRequest()->attributes->set('_controller', $newControllerAsString);

            // this actually makes Symfony to execute the other controller
            $event->setController($crudControllerCallable);
        }
    }

    private function isEasyAdminRequest(ControllerEvent $event): bool
    {
        // this is what menu items that link to Symfony routes use to
        // associate the route with some EasyAdmin dashboard
        if ($event->getRequest()->query->has('eaContext')) {
            return true;
        }

        // otherwise, check if the controller associated to the current request
        // implements the DashboardControllerInterface from EasyAdmin
        $controller = $event->getController();

        // if the controller is defined in a class, $controller is an array
        // otherwise do nothing because it's a Closure (rare but possible in Symfony)
        if (!\is_array($controller)) {
            return false;
        }

        $controllerInstance = $controller[0];

        return $controllerInstance instanceof DashboardControllerInterface;
    }

    private function getCrudController(Request $request): ?callable
    {
        $crudControllerFqcn = $request->query->get('crudController');
        $crudAction = $request->query->get('crudAction');

        if (null === $crudControllerFqcn || null === $crudAction) {
            return null;
        }

        $crudRequest = $request->duplicate();
        $crudRequest->attributes->set('_controller', [$crudControllerFqcn, $crudAction]);
        $crudControllerCallable = $this->controllerResolver->getController($crudRequest);

        if (false === $crudControllerCallable) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller "%s::%s".', $crudControllerFqcn, $crudAction));
        }

        if (!\is_array($crudControllerCallable)) {
            return null;
        }

        if (!$crudControllerCallable[0] instanceof CrudControllerInterface) {
            return null;
        }

        return $crudControllerCallable;
    }

    private function getDashboardControllerInstance(ControllerEvent $event): DashboardControllerInterface
    {
        if ($event->getRequest()->query->has('eaContext')) {
            $contextId = $event->getRequest()->query->get('eaContext');
            $dashboardControllerFqcn = $this->dashboardRegistry->getControllerFqcnByContextId($contextId);
            $newRequest = $event->getRequest()->duplicate(null, null, ['_controller' => $dashboardControllerFqcn.'::index']);

            return $this->controllerResolver->getController($newRequest)[0];
        }

        return $event->getController()[0];
    }

    private function createAdminContext(Request $request, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AdminContext
    {
        return $this->adminContextFactory->create($request, $dashboardController, $crudController);
    }

    private function getAdminContext(ControllerEvent $event): ?AdminContext
    {
        return $event->getRequest()->attributes->get(EasyAdminBundle::CONTEXT_ATTRIBUTE_NAME);
    }

    private function setAdminContext(ControllerEvent $event, AdminContext $adminContext): void
    {
        $event->getRequest()->attributes->set(EasyAdminBundle::CONTEXT_ATTRIBUTE_NAME, $adminContext);
    }
}
