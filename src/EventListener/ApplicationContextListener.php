<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\FormPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\IndexPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\AssetContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dashboard\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

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
    private $twig;
    private $tokenStorage;
    private $menuBuilder;

    public function __construct(ControllerResolverInterface $controllerResolver, Registry $doctrine, Environment $twig, ?TokenStorageInterface $tokenStorage, MenuBuilderInterface $menuBuilder)
    {
        $this->controllerResolver = $controllerResolver;
        $this->doctrine = $doctrine;
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->menuBuilder = $menuBuilder;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$this->isDashboardController($event->getController())) {
            return;
        }

        $crudControllerCallable = $this->getCrudController($event->getRequest());
        $crudControllerInstance = $crudControllerCallable[0];

        $this->createApplicationContext($event, $crudControllerInstance);
        $applicationContext = $this->getApplicationContext($event);
        // this makes the ApplicationContext available in all templates as a short named variable
        $this->twig->addGlobal('ea', $applicationContext);

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
        if (!$controllerInstance instanceof DashboardControllerInterface) {
            return false;
        }

        return true;
    }

    private function getCrudController(Request $request): ?callable
    {
        $crudControllerFqcn = $request->query->get('crud');
        $crudPage = $request->query->get('page');

        if (null === $crudControllerFqcn || null === $crudPage) {
            return null;
        }

        // TODO: VERY IMPORTANT: check that the controller is associated to the
        // current dashboard. Otherwise, anyone can access any app controller.

        $crudRequest = $request->duplicate();
        $crudRequest->attributes->set('_controller', [$crudControllerFqcn, $crudPage]);
        $crudControllerCallable = $this->controllerResolver->getController($crudRequest);

        if (false === $crudControllerCallable) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller "%s::%s".', $crudControllerFqcn, $crudPage));
        }

        if (!is_array($crudControllerCallable)) {
            return null;
        }

        if (!$crudControllerCallable[0] instanceof CrudControllerInterface) {
            return null;
        }

        return $crudControllerCallable;
    }

    private function createApplicationContext(ControllerEvent $event, ?CrudControllerInterface $crudControllerInstance): void
    {
        // creating the context is expensive, so it's created once and stored in the request
        // if the current request already has an ApplicationContext object, do nothing
        if ($this->getApplicationContext($event) instanceof ApplicationContext) {
            return;
        }

        $request = $event->getRequest();
        $dashboardControllerInstance = $event->getController()[0];
        $crudPage = $request->query->get('page');
        $entityId = $request->query->get('id');


        $dashboard = $this->getDashboard($event);
        $assets = $this->getAssets($dashboardControllerInstance, $crudControllerInstance);
        $crudConfig = $this->getCrudConfig($crudControllerInstance);
        $pageConfig = $this->getPageConfig($crudControllerInstance, $crudPage);
        [$entityConfig, $entityInstance] = $this->getDoctrineEntity($crudControllerInstance, $entityId);

        $applicationContext = new ApplicationContext($request, $this->tokenStorage, $dashboard, $this->menuBuilder, $assets, $crudConfig, $crudPage, $pageConfig, $entityConfig, $entityInstance);
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

    private function getDashboard(ControllerEvent $event): DashboardControllerInterface
    {
        /** @var DashboardControllerInterface $dashboard */
        $dashboard = $event->getController()[0];

        return $dashboard;
    }

    private function getAssets(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AssetContext
    {
        $dashboardAssets = $dashboardController->configureAssets()->getAsValueObject();

        if (null === $crudController) {
            return $dashboardAssets;
        }

        $crudAssets = $crudController->configureAssets()->getAsValueObject();

        return $dashboardAssets->mergeWith($crudAssets);
    }

    private function getCrudConfig(?CrudControllerInterface $crudController): ?CrudContext
    {
        if (null === $crudController) {
            return null;
        }

        return $crudController->configureCrud()->getAsValueObject();
    }

    /**
     * @return IndexPageConfig|DetailPageConfig|FormPageConfig|null
     */
    private function getPageConfig(?CrudControllerInterface $crudController, ?string $crudPage)
    {
        $pageConfigMethodName = 'configure'.ucfirst($crudPage).'Page';
        if (null === $crudController || !method_exists($crudController, $pageConfigMethodName)) {
            return null;
        }

        return $crudController->{$pageConfigMethodName}();
    }

    /**
     * @return [?EntityConfig, ?$entityInstance]
     */
    private function getDoctrineEntity(?CrudControllerInterface $crudController, $entityId): array
    {
        if (null === $crudController || null === $entityId) {
            return [null, null];
        }

        $entityFqcn = $crudController->configureCrud()->getAsValueObject()->getEntityClass();
        if (null === $entityFqcn) {
            return [null, null];
        }

        $entityManager = $this->getEntityManager($entityFqcn);
        $entityInstance = $this->getEntityInstance($entityManager, $entityFqcn, $entityId);
        $entityConfig = new EntityConfig($entityManager->getClassMetadata($entityFqcn), $entityId);

        return [$entityConfig, $entityInstance];
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
}
