<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
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
    private $controllerFactory;
    private $twig;

    public function __construct(AdminContextFactory $adminContextFactory, ControllerFactory $controllerFactory, Environment $twig)
    {
        $this->adminContextFactory = $adminContextFactory;
        $this->controllerFactory = $controllerFactory;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $contextId = $event->getRequest()->query->get('eaContext');
        $currentControllerInstance = $this->getCurrentControllerInstance($event);
        if (!$this->isEasyAdminRequest($contextId, $currentControllerInstance)) {
            return;
        }

        $dashboardControllerInstance = $currentControllerInstance instanceof DashboardControllerInterface
            ? $currentControllerInstance
            : $this->controllerFactory->getDashboardControllerInstance($contextId, $event->getRequest());
        if (null === $dashboardControllerInstance) {
            // this can only happen when a malicious user tries to hack the contextId value in the query string
            // don't throw an exception to prevent hackers from causing lots of exceptions in applications using EasyAdmin
            // and don't do anything else, to not leak that the application uses EasyAdmin
            return;
        }

        $crudId = $event->getRequest()->query->get('crudId');
        $crudAction = $event->getRequest()->query->get('crudAction');
        $crudControllerInstance = $this->controllerFactory->getCrudControllerInstance($crudId, $crudAction, $event->getRequest());

        if (null !== $crudId && null === $dashboardControllerInstance) {
            // this can only happen when a malicious user tries to hack the crudId value in the query string
            // don't throw an exception to prevent hackers from causing lots of exceptions in applications using EasyAdmin
            // and don't do anything else, to not leak that the application uses EasyAdmin
            return;
        }

        // creating the context is expensive, so it's created once and stored in the request
        // if the current request already has an AdminContext object, do nothing
        if (null === $adminContext = $this->getAdminContext($event)) {
            $adminContext = $this->createAdminContext($event->getRequest(), $dashboardControllerInstance, $crudControllerInstance);
        }

        $this->setAdminContext($event, $adminContext);

        // this makes the AdminContext available in all templates as a short named variable
        $this->twig->addGlobal('ea', $adminContext);

        // if the request is related to a CRUD controller, change the controller to be executed
        if (null !== $crudControllerInstance) {
            $crudControllerCallable = [$crudControllerInstance, $crudAction];

            // this makes Symfony believe that another controller is being executed
            // (e.g. this is needed for the autowiring of controller action arguments)
            $event->getRequest()->attributes->set('_controller', $crudControllerCallable);

            // this actually makes Symfony to execute the other controller
            $event->setController($crudControllerCallable);
        }
    }

    /**
     * Request is associated to EasyAdmin if one of these conditions meet:
     *  * current controller is an instance of DashboardControllerInterface (the single point of
     *    entry for all requests directly served by EasyAdmin)
     *  * the contextId passed via the 'eaContext' query string parameter is not null
     *    (that's used in menu items that link to Symfony routes not served by EasyAdmin, so
     *    those routes can still be associated with some EasyAdmin dashboard to display the menu, etc.).
     */
    private function isEasyAdminRequest(?string $contextId, $currentControllerInstance): bool
    {
        if (null !== $contextId) {
            return true;
        }

        return $currentControllerInstance instanceof DashboardControllerInterface;
    }

    private function getCurrentControllerInstance(ControllerEvent $event)
    {
        $controller = $event->getController();

        // if the controller is defined in a class, $controller is an array
        // otherwise do nothing because it's a Closure (rare but possible in Symfony)
        if (!\is_array($controller)) {
            return null;
        }

        return $controller[0];
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
