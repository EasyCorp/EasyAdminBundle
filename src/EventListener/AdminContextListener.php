<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
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
    private $controllerResolver;
    private $twig;

    public function __construct(AdminContextFactory $adminContextFactory, ControllerResolverInterface $controllerResolver, Environment $twig)
    {
        $this->adminContextFactory = $adminContextFactory;
        $this->controllerResolver = $controllerResolver;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$this->isDashboardController($event->getController())) {
            return;
        }

        $crudControllerCallable = $this->getCrudController($event->getRequest());
        $crudControllerInstance = null !== $crudControllerCallable ? $crudControllerCallable[0] : null;

        // creating the context is expensive, so it's created once and stored in the request
        // if the current request already has an AdminContext object, do nothing
        if (null === $adminContext = $this->getAdminContext($event)) {
            $dashboardControllerInstance = $event->getController()[0];
            $adminContext = $this->createAdminContext($event->getRequest(), $dashboardControllerInstance, $crudControllerInstance);
        }

        $this->setAdminContext($event, $adminContext);

        // this makes the AdminContext available in all templates as a short named variable
        $this->twig->addGlobal('ea', $adminContext);

        if (null !== $crudControllerInstance) {
            // Changes the controller associated to the current request to execute the
            // CRUD controller and page requested via the dashboard menu and actions
            $event->setController($crudControllerCallable);
        }
    }

    private function isDashboardController(callable $controller): bool
    {
        // if the controller is defined in a class, $controller is an array
        // otherwise do nothing because it's a Closure (rare but possible in Symfony)
        if (!\is_array($controller)) {
            return false;
        }

        $controllerInstance = $controller[0];

        // If the controller does not implement EasyAdmin's DashboardControllerInterface,
        // assume that the request is not related to EasyAdmin
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
