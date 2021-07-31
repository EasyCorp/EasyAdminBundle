<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextDirection;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
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
use function Symfony\Component\String\u;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminContextFactory
{
    private $cacheDir;
    private $translator;
    private $tokenStorage;
    private $menuFactory;
    private $crudControllers;
    private $entityFactory;

    public function __construct(string $cacheDir, TranslatorInterface $translator, ?TokenStorageInterface $tokenStorage, MenuFactory $menuFactory, CrudControllerRegistry $crudControllers, EntityFactory $entityFactory)
    {
        $this->cacheDir = $cacheDir;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->menuFactory = $menuFactory;
        $this->crudControllers = $crudControllers;
        $this->entityFactory = $entityFactory;
    }

    public function create(Request $request, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AdminContext
    {
        $crudAction = $request->query->get(EA::CRUD_ACTION);
        $validPageNames = [Crud::PAGE_INDEX, Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW];
        $pageName = \in_array($crudAction, $validPageNames, true) ? $crudAction : null;

        $dashboardDto = $this->getDashboardDto($request, $dashboardController);
        $assetDto = $this->getAssetDto($dashboardController, $crudController);
        $actionConfigDto = $this->getActionConfig($dashboardController, $crudController, $pageName);
        $filters = $this->getFilters($dashboardController, $crudController);

        $crudDto = $this->getCrudDto($this->crudControllers, $dashboardController, $crudController, $actionConfigDto, $filters, $crudAction, $pageName);
        $entityDto = $this->getEntityDto($request, $crudDto);
        $searchDto = $this->getSearchDto($request, $crudDto);
        $i18nDto = $this->getI18nDto($request, $dashboardDto, $crudDto, $entityDto);
        $templateRegistry = $this->getTemplateRegistry($dashboardController, $crudDto);
        $user = $this->getUser($this->tokenStorage);

        return new AdminContext($request, $user, $i18nDto, $this->crudControllers, $dashboardDto, $dashboardController, $assetDto, $crudDto, $entityDto, $searchDto, $this->menuFactory, $templateRegistry);
    }

    private function getDashboardDto(Request $request, DashboardControllerInterface $dashboardControllerInstance): DashboardDto
    {
        $dashboardRoutesCachePath = $this->cacheDir.'/'.CacheWarmer::DASHBOARD_ROUTES_CACHE;
        $dashboardControllerRoutes = !file_exists($dashboardRoutesCachePath) ? [] : require $dashboardRoutesCachePath;
        $dashboardController = \get_class($dashboardControllerInstance).'::index';
        $dashboardRouteName = null;

        foreach ($dashboardControllerRoutes as $routeName => $controller) {
            if ($controller === $dashboardController) {
                // needed for i18n routes, whose name follows the pattern "route_name.locale"
                $dashboardRouteName = explode('.', $routeName, 2)[0];

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

    private function getAssetDto(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AssetsDto
    {
        $defaultAssets = $dashboardController->configureAssets();

        if (null === $crudController) {
            return $defaultAssets->getAsDto();
        }

        return $crudController->configureAssets($defaultAssets)->getAsDto();
    }

    private function getCrudDto(CrudControllerRegistry $crudControllers, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ActionConfigDto $actionConfigDto, FilterConfigDto $filters, ?string $crudAction, ?string $pageName): ?CrudDto
    {
        if (null === $crudController) {
            return null;
        }

        $defaultCrud = $dashboardController->configureCrud();
        $crudDto = $crudController->configureCrud($defaultCrud)->getAsDto();

        $entityFqcn = $crudControllers->findEntityFqcnByCrudFqcn(\get_class($crudController));

        $crudDto->setControllerFqcn(\get_class($crudController));
        $crudDto->setActionsConfig($actionConfigDto);
        $crudDto->setFiltersConfig($filters);
        $crudDto->setCurrentAction($crudAction);
        $crudDto->setEntityFqcn($entityFqcn);
        $crudDto->setPageName($pageName);

        return $crudDto;
    }

    private function getActionConfig(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ?string $pageName): ActionConfigDto
    {
        if (null === $crudController || null === $pageName) {
            return new ActionConfigDto();
        }

        $defaultActionConfig = $dashboardController->configureActions();

        return $crudController->configureActions($defaultActionConfig)->getAsDto($pageName);
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
        $defaultTextDirection = \in_array($localePrefix, ['ar', 'fa', 'he']) ? TextDirection::RTL : TextDirection::LTR;
        $textDirection = $configuredTextDirection ?? $defaultTextDirection;

        $translationDomain = $dashboardDto->getTranslationDomain();

        $translationParameters = [];
        if (null !== $crudDto) {
            $translationParameters['%entity_name%'] = $entityName = basename(str_replace('\\', '/', $crudDto->getEntityFqcn()));
            $translationParameters['%entity_as_string%'] = null === $entityDto ? '' : $entityDto->toString();
            $translationParameters['%entity_id%'] = $entityId = $request->query->get(EA::ENTITY_ID);
            $translationParameters['%entity_short_id%'] = null === $entityId ? null : u((string) $entityId)->truncate(7)->toString();

            $entityInstance = null === $entityDto ? null : $entityDto->getInstance();
            $pageName = $crudDto->getCurrentPage();
            $translatedSingularLabel = $this->translator->trans($crudDto->getEntityLabelInSingular($entityInstance, $pageName) ?? $entityName, $translationParameters, $translationDomain);
            $translatedPluralLabel = $this->translator->trans($crudDto->getEntityLabelInPlural($entityInstance, $pageName) ?? $entityName, $translationParameters, $translationDomain);
            $crudDto->setEntityLabelInSingular($translatedSingularLabel);
            $crudDto->setEntityLabelInPlural($translatedPluralLabel);

            $translationParameters['%entity_label_singular%'] = $translatedSingularLabel;
            $translationParameters['%entity_label_plural%'] = $translatedPluralLabel;
        }

        return new I18nDto($locale, $textDirection, $translationDomain, $translationParameters);
    }

    public function getSearchDto(Request $request, ?CrudDto $crudDto): ?SearchDto
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
        if (null === $tokenStorage || !$token = $tokenStorage->getToken()) {
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

        return $this->entityFactory->create($crudDto->getEntityFqcn(), $request->query->get(EA::ENTITY_ID), $crudDto->getEntityPermission());
    }
}
