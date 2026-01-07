<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextDirection;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory\MenuFactoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminRouteGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use function Symfony\Component\String\u;
use function Symfony\Component\Translation\t;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminContextFactory
{
    public function __construct(
        private readonly string $buildDir,
        private readonly ?TokenStorageInterface $tokenStorage,
        private readonly MenuFactoryInterface $menuFactory,
        private readonly CrudControllerRegistry $crudControllers,
        private readonly EntityFactory $entityFactory,
        private readonly AdminRouteGeneratorInterface $adminRouteGenerator,
        private readonly ActionFactory $actionFactory,
    ) {
    }

    public function create(Request $request, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ?string $actionName = null): AdminContext
    {
        $crudAction = $actionName ?? $request->query->get(EA::CRUD_ACTION);
        $validPageNames = [Crud::PAGE_INDEX, Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW];
        $pageName = \in_array($crudAction, $validPageNames, true) ? $crudAction : null;

        $dashboardDto = $this->getDashboardDto($request, $dashboardController);
        $assetDto = $this->getAssetDto($dashboardController, $crudController, $pageName);
        $filters = $this->getFilters($dashboardController, $crudController);
        $user = $this->getUser($this->tokenStorage);

        // build a first version of CrudDto without actions so we can create AdminContext, which is
        // needed for action extensions; later, we'll update the CrudDto object with the full action config
        $crudDto = $this->getCrudDto($this->crudControllers, $dashboardController, $crudController, new ActionConfigDto(), $filters, $crudAction, $pageName);
        $entityDto = $this->getEntityDto($request, $crudDto);
        $searchDto = $this->getSearchDto($request, $crudDto);
        $i18nDto = $this->getI18nDto($request, $dashboardDto, $crudDto, $entityDto);
        $templateRegistry = $this->getTemplateRegistry($dashboardController, $crudDto);
        $usePrettyUrls = $this->adminRouteGenerator->usesPrettyUrls();

        // build sub-contexts
        $requestContext = new RequestContext($request, $user);
        $crudContext = new CrudContext($crudDto, $entityDto, $searchDto, $this->crudControllers);
        $dashboardContext = new DashboardContext($dashboardDto, $dashboardController::class, $assetDto, $usePrettyUrls);
        $i18nContext = new I18nContext($i18nDto, $templateRegistry);

        // set lazy menu builders to avoid circular dependencies
        // (MenuFactory needs AdminContext, but we're still creating it)
        $dashboardContext->setMainMenuBuilder(function () use ($dashboardController) {
            $configuredMenuItems = $dashboardController->configureMenuItems();
            $mainMenuItems = \is_array($configuredMenuItems) ? $configuredMenuItems : iterator_to_array($configuredMenuItems, false);

            return $this->menuFactory->createMainMenu($mainMenuItems);
        });

        $dashboardContext->setUserMenuBuilder(function () use ($dashboardController, $user) {
            if (null === $user) {
                return UserMenu::new()->getAsDto();
            }

            return $this->menuFactory->createUserMenu($dashboardController->configureUserMenu($user));
        });

        $adminContext = new AdminContext($requestContext, $crudContext, $dashboardContext, $i18nContext);

        // build actions with extensions and update the CrudDto
        // (ActionFactory needs the full AdminContext to apply extensions)
        $actionConfigDto = $this->actionFactory->buildActionsConfig($dashboardController, $crudController, $adminContext, $pageName);
        $crudDto?->setActionsConfig($actionConfigDto);

        return $adminContext;
    }

    private function getDashboardDto(Request $request, DashboardControllerInterface $dashboardControllerInstance): DashboardDto
    {
        $dashboardRoutesCachePath = $this->buildDir.'/'.CacheWarmer::DASHBOARD_ROUTES_CACHE;
        $dashboardControllerRoutes = !file_exists($dashboardRoutesCachePath) ? [] : require $dashboardRoutesCachePath;
        $dashboardController = $dashboardControllerInstance::class.'::index';
        $dashboardRouteName = null;

        foreach ($dashboardControllerRoutes as $routeName => $controller) {
            if ($controller === $dashboardController) {
                // if present, remove the suffix of i18n route names (it's the content after the last dot
                // in the route name; e.g. 'dashboard.en' -> remove '.en', 'admin.index.en_US' -> remove '.en_US')
                $dashboardRouteName = preg_replace('~\.[a-z]{2}(_[A-Z]{2})?$~', '', $routeName);

                break;
            }
        }

        if (null === $dashboardRouteName) {
            throw new \RuntimeException(sprintf('The name of the route associated to "%s" cannot be determined. Clear the application cache to run the EasyAdmin cache warmer, which generates the needed data to find this route.', $dashboardController));
        }

        $dashboardDto = $dashboardControllerInstance->configureDashboard()->getAsDto();
        $dashboardDto->setRouteName($dashboardRouteName);

        return $dashboardDto;
    }

    private function getAssetDto(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ?string $pageName): AssetsDto
    {
        $defaultAssets = $dashboardController->configureAssets();

        if (null === $crudController) {
            return $defaultAssets->getAsDto();
        }

        return $crudController->configureAssets($defaultAssets)->getAsDto()->loadedOn($pageName);
    }

    private function getCrudDto(CrudControllerRegistry $crudControllers, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ActionConfigDto $actionConfigDto, FilterConfigDto $filters, ?string $crudAction, ?string $pageName): ?CrudDto
    {
        if (null === $crudController) {
            return null;
        }

        $defaultCrud = $dashboardController->configureCrud();
        $crudDto = $crudController->configureCrud($defaultCrud)->getAsDto();

        $entityFqcn = $crudControllers->findEntityFqcnByCrudFqcn($crudController::class);

        $crudDto->setControllerFqcn($crudController::class);
        $crudDto->setActionsConfig($actionConfigDto);
        $crudDto->setFiltersConfig($filters);
        $crudDto->setCurrentAction($crudAction);
        $crudDto->setEntityFqcn($entityFqcn);
        $crudDto->setPageName($pageName);

        return $crudDto;
    }

    private function getFilters(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): FilterConfigDto
    {
        if (null === $crudController) {
            return new FilterConfigDto();
        }

        $defaultFilterConfig = $dashboardController->configureFilters();

        return $crudController->configureFilters($defaultFilterConfig)->getAsDto();
    }

    private function getTemplateRegistry(DashboardControllerInterface $dashboardController, ?CrudDto $crudDto): TemplateRegistry
    {
        $templateRegistry = TemplateRegistry::new();

        $defaultCrudDto = $dashboardController->configureCrud()->getAsDto();
        $templateRegistry->setTemplates($defaultCrudDto->getOverriddenTemplates());

        if (null !== $crudDto) {
            $templateRegistry->setTemplates($crudDto->getOverriddenTemplates());
        }

        return $templateRegistry;
    }

    private function getI18nDto(Request $request, DashboardDto $dashboardDto, ?CrudDto $crudDto, ?EntityDto $entityDto): I18nDto
    {
        $locale = $request->getLocale();

        $configuredTextDirection = $dashboardDto->getTextDirection();
        $localePrefix = strtolower(substr($locale, 0, 2));
        $defaultTextDirection = \in_array($localePrefix, ['ar', 'fa', 'he'], true) ? TextDirection::RTL : TextDirection::LTR;
        $textDirection = $configuredTextDirection ?? $defaultTextDirection;

        $translationDomain = $dashboardDto->getTranslationDomain();

        $translationParameters = [];
        if (null !== $crudDto) {
            $translationParameters['%entity_name%'] = $entityName = basename(str_replace('\\', '/', $crudDto->getEntityFqcn()));
            $translationParameters['%entity_as_string%'] = null === $entityDto ? '' : (string) $entityDto;
            // when using pretty URLs, the entity ID is passed as a request attribute (it's part of the route path);
            // in legacy URLs, the entity ID is passed as a regular query parameter
            $translationParameters['%entity_id%'] = $entityId = $request->attributes->get(EA::ENTITY_ID) ?? $request->query->get(EA::ENTITY_ID);
            $translationParameters['%entity_short_id%'] = null === $entityId ? null : u($entityId)->truncate(7)->toString();

            $entityInstance = null === $entityDto ? null : $entityDto->getInstance();
            $pageName = $crudDto->getCurrentPage();

            $singularLabel = $crudDto->getEntityLabelInSingular($entityInstance, $pageName);
            if (!$singularLabel instanceof TranslatableInterface) {
                $singularLabel = t($singularLabel ?? $entityName, $translationParameters, $translationDomain);
            }

            $pluralLabel = $crudDto->getEntityLabelInPlural($entityInstance, $pageName);
            if (!$pluralLabel instanceof TranslatableInterface) {
                $pluralLabel = t($pluralLabel ?? $entityName, $translationParameters, $translationDomain);
            }

            $crudDto->setEntityLabelInSingular($singularLabel);
            $crudDto->setEntityLabelInPlural($pluralLabel);

            $translationParameters['%entity_label_singular%'] = $singularLabel;
            $translationParameters['%entity_label_plural%'] = $pluralLabel;
        }

        return new I18nDto($locale, $textDirection, $translationDomain, $translationParameters);
    }

    public function getSearchDto(Request $request, ?CrudDto $crudDto): ?SearchDto
    {
        if (null === $crudDto) {
            return null;
        }

        $searchableProperties = $crudDto->getSearchFields();
        $query = $request->query->has(EA::QUERY) ? (string) $request->query->get(EA::QUERY) : null;
        $defaultSort = $crudDto->getDefaultSort();
        $customSort = $request->query->all(EA::SORT);
        $appliedFilters = $request->query->all(EA::FILTERS);
        $searchMode = $crudDto->getSearchMode();

        return new SearchDto($request, $searchableProperties, $query, $defaultSort, $customSort, $appliedFilters, $searchMode);
    }

    // Copied from https://github.com/symfony/twig-bridge/blob/master/AppVariable.php
    // (c) Fabien Potencier <fabien@symfony.com> - MIT License
    private function getUser(?TokenStorageInterface $tokenStorage): ?UserInterface
    {
        if (null === $token = $tokenStorage?->getToken()) {
            return null;
        }

        $user = $token->getUser();

        return \is_object($user) ? $user : null;
    }

    private function getEntityDto(Request $request, ?CrudDto $crudDto): ?EntityDto
    {
        if (null === $crudDto) {
            return null;
        }

        // when using pretty URLs, the entity ID is passed as a request attribute (it's part of the route path);
        // in legacy URLs, the entity ID is passed as a regular query parameter
        $entityId = $request->attributes->get(EA::ENTITY_ID) ?? $request->query->get(EA::ENTITY_ID);

        return $this->entityFactory->create($crudDto->getEntityFqcn(), $entityId, $crudDto->getEntityPermission());
    }
}
