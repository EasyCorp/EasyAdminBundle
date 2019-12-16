<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\TemplateRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Builder\ItemCollectionBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
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
    private $twig;
    private $tokenStorage;
    private $menuBuilder;

    public function __construct(ControllerResolverInterface $controllerResolver, Environment $twig, ?TokenStorageInterface $tokenStorage, ItemCollectionBuilderInterface $menuBuilder)
    {
        $this->controllerResolver = $controllerResolver;
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
        $crudAction = $request->query->get('crudAction');

        $dashboardDto = $this->getDashboard($event);
        $assetDto = $this->getAssets($dashboardControllerInstance, $crudControllerInstance);
        $crudDto = $this->getCrudConfig($dashboardControllerInstance, $crudControllerInstance, $crudAction);
        if (null !== $crudDto) {
            $crudPageDto = $this->getCrudPageConfig($dashboardControllerInstance, $crudControllerInstance, $crudAction);
            $crudDto = $crudDto->with(['crudPageDto' => $crudPageDto]);
        }
        $templateRegistry = $this->getTemplateRegistry($dashboardControllerInstance, $crudDto);
        $i18nDto = $this->getI18nConfig($request, $dashboardDto, $crudDto);

        $applicationContext = new ApplicationContext($request, $this->tokenStorage, $i18nDto, $dashboardDto, $dashboardControllerInstance, $this->menuBuilder, $assetDto, $crudDto, $templateRegistry);
        $this->setApplicationContext($event, $applicationContext);
    }

    private function getApplicationContext(ControllerEvent $event): ?ApplicationContext
    {
        return $event->getRequest()->attributes->get(EasyAdminBundle::REQUEST_ATTRIBUTE_NAME);
    }

    private function setApplicationContext(ControllerEvent $event, ApplicationContext $applicationContext): void
    {
        $event->getRequest()->attributes->set(EasyAdminBundle::REQUEST_ATTRIBUTE_NAME, $applicationContext);
    }

    private function getDashboard(ControllerEvent $event): DashboardDto
    {
        /** @var \EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface $dashboardControllerInstance */
        $dashboardControllerInstance = $event->getController()[0];
        $currentRouteName = $event->getRequest()->attributes->get('_route');

        return $dashboardControllerInstance
            ->configureDashboard()
            ->getAsDto()
            ->with(['routeName' => $currentRouteName]);
    }

    private function getAssets(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AssetDto
    {
        $defaultAssetConfig = $dashboardController->configureAssets();

        if (null === $crudController) {
            return $defaultAssetConfig->getAsDto();
        }

        return $crudController->configureAssets($defaultAssetConfig)->getAsDto();
    }

    private function getCrudConfig(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ?string $crudAction): ?CrudDto
    {
        if (null === $crudController) {
            return null;
        }

        $defaultCrudConfig = $dashboardController->configureCrud();

        return $crudController->configureCrud($defaultCrudConfig)
            ->getAsDto()
            ->with(['actionName' => $crudAction]);
    }

    private function getTemplateRegistry(DashboardControllerInterface $dashboardController, ?CrudDto $crudDto): TemplateRegistry
    {
        $templateRegistry = TemplateRegistry::new();

        $defaultCrudDto = $dashboardController->configureCrud()->getAsDto(false);
        $templateRegistry->addTemplates($defaultCrudDto->get('overriddenTemplates'));

        if (null !== $crudDto) {
            $templateRegistry->addTemplates($crudDto->get('overriddenTemplates'));
        }

        return $templateRegistry;
    }

    private function getCrudPageConfig(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ?string $crudAction): ?CrudPageDto
    {
        $pageConfigMethodName = 'configure'.ucfirst($crudAction).'Page';
        if (null === $crudController || !method_exists($crudController, $pageConfigMethodName)) {
            return null;
        }

        $defaultPageConfig = $dashboardController->{$pageConfigMethodName}();

        return $crudController->{$pageConfigMethodName}($defaultPageConfig)->getAsDto();
    }

    private function getI18nConfig(Request $request, DashboardDto $dashboardDto, ?CrudDto $crudDto): I18nDto
    {
        $locale = $request->getLocale();

        $configuredTextDirection = $dashboardDto->getTextDirection();
        $localePrefix = strtolower(substr($locale, 0, 2));
        $defaultTextDirection = \in_array($localePrefix, ['ar', 'fa', 'he']) ? 'rtl' : 'ltr';
        $textDirection = $configuredTextDirection ?? $defaultTextDirection;

        $translationDomain = $dashboardDto->getTranslationDomain();

        $translationParameters = [];
        if (null !== $crudDto) {
            $translationParameters['%entity_label_singular%'] = $crudDto->getLabelInSingular();
            $translationParameters['%entity_label_plural%'] = $crudDto->getLabelInPlural();
        }

        return new I18nDto($locale, $textDirection, $translationDomain, $translationParameters);
    }
}
