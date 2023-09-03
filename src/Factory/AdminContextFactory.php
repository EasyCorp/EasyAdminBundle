<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use EasyCorp\Bundle\EasyAdminBundle\Config\CrudInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextDirection;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

use function Symfony\Component\String\u;
use function Symfony\Component\Translation\t;

final class AdminContextFactory implements AdminContextFactoryInterface
{
    private string $cacheDir;

    private ?TokenStorageInterface $tokenStorage;

    private MenuFactoryInterface $menuFactory;

    private CrudControllerRegistryInterface $crudControllers;

    private EntityFactoryInterface $entityFactory;

    public function __construct(
        string $cacheDir,
        ?TokenStorageInterface $tokenStorage,
        MenuFactoryInterface $menuFactory,
        CrudControllerRegistryInterface $crudControllers,
        EntityFactoryInterface $entityFactory
    ) {
        $this->cacheDir = $cacheDir;
        $this->tokenStorage = $tokenStorage;
        $this->menuFactory = $menuFactory;
        $this->crudControllers = $crudControllers;
        $this->entityFactory = $entityFactory;
    }

    public function create(
        Request $request,
        DashboardControllerInterface $dashboardController,
        ?CrudControllerInterface $crudController
    ): AdminContext {
        $crudAction = $request->query->get(EA::CRUD_ACTION);
        $validPageNames = [
            CrudInterface::PAGE_INDEX,
            CrudInterface::PAGE_DETAIL,
            CrudInterface::PAGE_EDIT,
            CrudInterface::PAGE_NEW,
        ];
        $pageName = \in_array($crudAction, $validPageNames, true) ? $crudAction : null;

        $dashboardDto = $this->getDashboardDto($request, $dashboardController);
        $assetDto = $this->getAssetDto($dashboardController, $crudController, $pageName);
        $actionConfigDto = $this->getActionConfig($dashboardController, $crudController, $pageName);
        $filters = $this->getFilters($dashboardController, $crudController);

        $crudDto = $this->getCrudDto(
            $this->crudControllers,
            $dashboardController,
            $crudController,
            $actionConfigDto,
            $filters,
            $crudAction,
            $pageName
        );
        $entityDto = $this->getEntityDto($request, $crudDto);
        $searchDto = $this->getSearchDto($request, $crudDto);
        $i18nDto = $this->getI18nDto($request, $dashboardDto, $crudDto, $entityDto);
        $templateRegistry = $this->getTemplateRegistry($dashboardController, $crudDto);
        $user = $this->getUser($this->tokenStorage);

        return new AdminContext(
            $request,
            $user,
            $i18nDto,
            $this->crudControllers,
            $dashboardDto,
            $dashboardController,
            $assetDto,
            $crudDto,
            $entityDto,
            $searchDto,
            $this->menuFactory,
            $templateRegistry
        );
    }

    private function getDashboardDto(
        Request $request,
        DashboardControllerInterface $dashboardControllerInstance
    ): DashboardDtoInterface {
        $dashboardRoutesCachePath = $this->cacheDir.'/'.CacheWarmer::DASHBOARD_ROUTES_CACHE;
        $dashboardControllerRoutes = !file_exists($dashboardRoutesCachePath) ? [] : require $dashboardRoutesCachePath;
        $dashboardController = $dashboardControllerInstance::class.'::index';
        $dashboardRouteName = null;

        foreach ($dashboardControllerRoutes as $routeName => $controller) {
            if ($controller === $dashboardController) {
                // if present, remove the suffix of i18n route names (it's a two-letter locale at the end
                // of the route name; e.g. 'dashboard.en' -> remove '.en', 'admin.index.es' -> remove '.es')
                $dashboardRouteName = preg_replace('~\.\w{2}$~', '', $routeName);

                break;
            }
        }

        if (null === $dashboardRouteName) {
            throw new \RuntimeException(
                sprintf(
                    'The name of the route associated to "%s" cannot be determined. Clear the application cache to run the EasyAdmin cache warmer, which generates the needed data to find this route.',
                    $dashboardController
                )
            );
        }

        $dashboardDto = $dashboardControllerInstance->configureDashboard()->getAsDto();
        $dashboardDto->setRouteName($dashboardRouteName);

        return $dashboardDto;
    }

    private function getAssetDto(
        DashboardControllerInterface $dashboardController,
        ?CrudControllerInterface $crudController,
        ?string $pageName
    ): AssetsDtoInterface {
        $defaultAssets = $dashboardController->configureAssets();

        if (null === $crudController) {
            return $defaultAssets->getAsDto();
        }

        return $crudController->configureAssets($defaultAssets)->getAsDto()->loadedOn($pageName);
    }

    private function getCrudDto(
        CrudControllerRegistryInterface $crudControllers,
        DashboardControllerInterface $dashboardController,
        ?CrudControllerInterface $crudController,
        ActionConfigDtoInterface $actionConfigDto,
        FilterConfigDtoInterface $filters,
        ?string $crudAction,
        ?string $pageName
    ): ?CrudDtoInterface {
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

    private function getActionConfig(
        DashboardControllerInterface $dashboardController,
        ?CrudControllerInterface $crudController,
        ?string $pageName
    ): ActionConfigDtoInterface {
        if (null === $crudController) {
            return new ActionConfigDto();
        }

        $defaultActionConfig = $dashboardController->configureActions();

        return $crudController->configureActions($defaultActionConfig)->getAsDto($pageName);
    }

    private function getFilters(
        DashboardControllerInterface $dashboardController,
        ?CrudControllerInterface $crudController
    ): FilterConfigDtoInterface {
        if (null === $crudController) {
            return new FilterConfigDto();
        }

        $defaultFilterConfig = $dashboardController->configureFilters();

        return $crudController->configureFilters($defaultFilterConfig)->getAsDto();
    }

    private function getTemplateRegistry(
        DashboardControllerInterface $dashboardController,
        ?CrudDtoInterface $crudDto
    ): TemplateRegistryInterface {
        $templateRegistry = TemplateRegistry::new();

        $defaultCrudDto = $dashboardController->configureCrud()->getAsDto();
        $templateRegistry->setTemplates($defaultCrudDto->getOverriddenTemplates());

        if (null !== $crudDto) {
            $templateRegistry->setTemplates($crudDto->getOverriddenTemplates());
        }

        return $templateRegistry;
    }

    private function getI18nDto(
        Request $request,
        DashboardDtoInterface $dashboardDto,
        ?CrudDtoInterface $crudDto,
        ?EntityDtoInterface $entityDto
    ): I18nDtoInterface {
        $locale = $request->getLocale();

        $configuredTextDirection = $dashboardDto->getTextDirection();
        $localePrefix = strtolower(substr($locale, 0, 2));
        $defaultTextDirection = \in_array(
            $localePrefix,
            ['ar', 'fa', 'he'],
            true
        ) ? TextDirection::RTL : TextDirection::LTR;
        $textDirection = $configuredTextDirection ?? $defaultTextDirection;

        $translationDomain = $dashboardDto->getTranslationDomain();

        $translationParameters = [];
        if (null !== $crudDto) {
            $translationParameters['%entity_name%'] = $entityName = basename(
                str_replace('\\', '/', $crudDto->getEntityFqcn())
            );
            $translationParameters['%entity_as_string%'] = null === $entityDto ? '' : $entityDto->toString();
            $translationParameters['%entity_id%'] = $entityId = $request->query->get(EA::ENTITY_ID);
            $translationParameters['%entity_short_id%'] = null === $entityId ? null : u($entityId)->truncate(
                7
            )->toString();

            $entityInstance = $entityDto?->getInstance();
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

    public function getSearchDto(Request $request, ?CrudDtoInterface $crudDto): ?SearchDtoInterface
    {
        if (null === $crudDto) {
            return null;
        }

        $queryParams = $request->query->all();
        $searchableProperties = $crudDto->getSearchFields();
        $query = $queryParams[EA::QUERY] ?? null;
        $defaultSort = $crudDto->getDefaultSort();
        $customSort = $queryParams[EA::SORT] ?? [];
        $appliedFilters = $queryParams[EA::FILTERS] ?? [];

        return new SearchDto($request, $searchableProperties, $query, $defaultSort, $customSort, $appliedFilters);
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

    private function getEntityDto(Request $request, ?CrudDtoInterface $crudDto): ?EntityDtoInterface
    {
        if (null === $crudDto) {
            return null;
        }

        return $this->entityFactory->create(
            $crudDto->getEntityFqcn(),
            $request->query->get(EA::ENTITY_ID),
            $crudDto->getEntityPermission()
        );
    }
}
