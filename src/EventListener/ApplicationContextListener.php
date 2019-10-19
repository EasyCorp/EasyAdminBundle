<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityConfig;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EntityAdminControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dashboard\DashboardConfig;
use EasyCorp\Bundle\EasyAdminBundle\Dashboard\DashboardInterface;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuProvider;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Initializes the ApplicationContext variable and stores it as a request attribute.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ApplicationContextListener
{
    private $controllerResolver;
    private $doctrine;
    private $menuProvider;

    public function __construct(ControllerResolverInterface $controllerResolver, Registry $doctrine, MenuProvider $menuProvider)
    {
        $this->controllerResolver = $controllerResolver;
        $this->doctrine = $doctrine;
        $this->menuProvider = $menuProvider;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$this->isEasyAdminController($event->getController())) {
            return;
        }

        $this->createApplicationContext($event);
        $this->setController($event);
    }

    private function isEasyAdminController(callable $controller): bool
    {
        // if the controller is defined in a class, $controller is an array
        // otherwise do nothing because it's a Closure (rare but possible in Symfony)
        if (!\is_array($controller)) {
            return false;
        }

        $controllerInstance = $controller[0];

        // If the controller does not implement EasyAdmin's DashboardInterface,
        // assume that the request is not related to EasyAdmin
        if (!$controllerInstance instanceof DashboardInterface) {
            return false;
        }

        return true;
    }

    private function createApplicationContext(ControllerEvent $event): void
    {
        // creating the context is expensive, so it's created once and stored in the request
        // if the current request already has an ApplicationContext object, do nothing
        if ($this->getApplicationContext($event) instanceof ApplicationContext) {
            return;
        }

        $request = $event->getRequest();
        $dashboard = $this->getDashboard($event);
        $menu = $this->getMenu($dashboard);

        $entityFqcn = $this->getEntityFqcn($request);
        if (null === $entityFqcn) {
            $entityInstance = $entityConfig = null;
        } else {
            $entityManager = $this->getEntityManager($entityFqcn);
            $entityInstance = $this->getEntityInstance($entityManager, $entityFqcn, $request->query->get('id'));
            $entityConfig = new EntityConfig($entityManager, $entityInstance, $entityFqcn);
        }

        $applicationContext = new ApplicationContext($request, $dashboard, $menu, $entityConfig, $entityInstance);
        $this->setApplicationContext($event, $applicationContext);
    }

    private function getApplicationContext(ControllerEvent $event): ?ApplicationContext
    {
        return $event->getRequest()->attributes->get(ApplicationContext::ATTRIBUTE_KEY);
    }

    private function setApplicationContext(ControllerEvent $event, ApplicationContext $applicationContext): void
    {
        $event->getRequest()->attributes->set(ApplicationContext::ATTRIBUTE_KEY, $applicationContext);
    }

    private function getDashboard(ControllerEvent $event): DashboardInterface
    {
        /** @var DashboardInterface $dashboard */
        $dashboard = $event->getController()[0];

        return $dashboard;
    }

    private function getMenu(DashboardInterface $dashboard): MenuProviderInterface
    {
        foreach ($dashboard->getMenuItems() as $menuItem) {
            $this->menuProvider->addItem($menuItem);
        }

        return $this->menuProvider;
    }

    private function getEntityFqcn(Request $request): ?string
    {
        if (null === $controllerClass = $request->query->get('controller')) {
            return null;
        }

        if (!$this->classImplements($controllerClass, EntityAdminControllerInterface::class)) {
            return null;
        }

        $entityClassFqcn = $controllerClass::{'getEntityClass'}();

        return $entityClassFqcn;
    }

    private function getEntityManager(string $entityClass): ObjectManager
    {
        if (null === $entityManager = $this->doctrine->getManagerForClass($entityClass)) {
            throw new \RuntimeException(sprintf('There is no Doctrine Entity Manager defined for the "%s" class', $entityClass));
        }

        return $entityManager;
    }

    private function getEntityInstance(ObjectManager $entityManager, string $entityClass, $entityId)
    {
        // TODO: get the real 'primary_key_field_name' of the entity
        if (null === $entityInstance = $entityManager->getRepository($entityClass)->find($entityId)) {
            throw new EntityNotFoundException(['entity_name' => $entityClass, 'entity_id_name' => '...', 'entity_id_value' => $entityId]);
        }

        return $entityInstance;
    }

    /**
     * Changes the controller associated to the current request to execute the
     * controller and action requested via the dashboard menu and actions.
     */
    private function setController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $controllerClass = $request->query->get('controller');
        $controllerMethod = $request->query->get('action');

        if (null === $controllerClass || null === $controllerMethod) {
            return;
        }

        // TODO: VERY IMPORTANT: check that the controller is associated to the
        // current dashboard. Otherwise, anyone can access any app controller.

        $request->attributes->set('_controller', [$controllerClass, $controllerMethod]);
        $newController = $this->controllerResolver->getController($request);

        if (false === $newController) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller "%s::%s".', $controllerClass, $controllerMethod));
        }

        $event->setController($newController);
    }

    private function classImplements(string $classFqcn, string $interfaceFqcn): bool
    {
        $implementedInterfaces = class_implements($classFqcn);

        return $implementedInterfaces && \in_array($interfaceFqcn, $implementedInterfaces, true);
    }
}
