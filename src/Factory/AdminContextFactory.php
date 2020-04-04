<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminContextFactory
{
    private $tokenStorage;
    private $menuFactory;
    private $crudControllers;
    private $entityFactory;

    public function __construct(?TokenStorageInterface $tokenStorage, MenuFactory $menuFactory, iterable $crudControllers, EntityFactory $entityFactory)
    {
        $this->tokenStorage = $tokenStorage;
        $this->menuFactory = $menuFactory;
        $this->crudControllers = CrudControllerRegistry::new($crudControllers);
        $this->entityFactory = $entityFactory;
    }

    public function create(Request $request, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AdminContext
    {
        $crudAction = $request->query->get('crudAction');
        $validPageNames = [Crud::PAGE_INDEX, Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW];
        $pageName = \in_array($crudAction, $validPageNames, true) ? $crudAction : null;

        $dashboardDto = $this->getDashboardDto($request, $dashboardController);
        $assetDto = $this->getAssetDto($dashboardController, $crudController);
        $actionConfigDto = $this->getActionConfig($dashboardController, $crudController, $pageName);
        $filters = $this->getFilters($dashboardController, $crudController);

        $crudDto = $this->getCrudDto($this->crudControllers, $dashboardController, $crudController, $actionConfigDto, $filters, $crudAction, $pageName);
        $entityDto = $this->getEntityDto($request, $crudDto);
        $searchDto = $this->getSearchDto($request, $crudDto);
        $i18nDto = $this->getI18nDto($request, $dashboardDto, $crudDto);
        $templateRegistry = $this->getTemplateRegistry($dashboardController, $crudDto);
        $user = $this->getUser($this->tokenStorage);

        return new AdminContext($request, $user, $i18nDto, $this->crudControllers, $dashboardDto, $dashboardController, $assetDto, $crudDto, $entityDto, $searchDto, $this->menuFactory, $templateRegistry);
    }

    private function getDashboardDto(Request $request, DashboardControllerInterface $dashboardControllerInstance): DashboardDto
    {
        $currentRouteName = $request->attributes->get('_route');

        /** @var DashboardDto $dashboardDto */
        $dashboardDto = $dashboardControllerInstance->configureDashboard()->getAsDto();
        $dashboardDto->setRouteName($currentRouteName);

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

    private function getCrudDto(CrudControllerRegistry $crudControllerRegistry, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ActionConfigDto $actionConfigDto, FilterConfigDto $filters, ?string $crudAction, ?string $pageName): ?CrudDto
    {
        if (null === $crudController) {
            return null;
        }

        $defaultCrud = $dashboardController->configureCrud();
        $crudDto = $crudController->configureCrud($defaultCrud)->getAsDto();

        $entityFqcn = $crudControllerRegistry->getEntityFqcnByControllerFqcn(\get_class($crudController));
        $entityClassName = basename(str_replace('\\', '/', $entityFqcn));
        $entityName = empty($entityClassName) ? 'Undefined' : $entityClassName;

        $crudDto->setActionsConfig($actionConfigDto);
        $crudDto->setFiltersConfig($filters);
        $crudDto->setCurrentAction($crudAction);
        $crudDto->setEntityFqcn($entityFqcn);
        $crudDto->setEntityLabelInSingular($crudDto->getEntityLabelInSingular() ?? $entityName);
        $crudDto->setEntityLabelInPlural($crudDto->getEntityLabelInPlural() ?? $entityName);
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

    private function getI18nDto(Request $request, DashboardDto $dashboardDto, ?CrudDto $crudDto): I18nDto
    {
        $locale = $request->getLocale();

        $configuredTextDirection = $dashboardDto->getTextDirection();
        $localePrefix = strtolower(substr($locale, 0, 2));
        $defaultTextDirection = \in_array($localePrefix, ['ar', 'fa', 'he']) ? 'rtl' : 'ltr';
        $textDirection = $configuredTextDirection ?? $defaultTextDirection;

        $translationDomain = $dashboardDto->getTranslationDomain();

        $translationParameters = [];
        if (null !== $crudDto) {
            $translationParameters['%entity_label_singular%'] = $crudDto->getEntityLabelInSingular();
            $translationParameters['%entity_label_plural%'] = $crudDto->getEntityLabelInPlural();
            $translationParameters['%entity_name%'] = basename(str_replace('\\', '/', $crudDto->getEntityFqcn()));
            $translationParameters['%entity_id%'] = $request->query->get('entityId');
        }

        return new I18nDto($locale, $textDirection, $translationDomain, $translationParameters);
    }

    public function getSearchDto(Request $request, ?CrudDto $crudDto): ?SearchDto
    {
        if (null === $crudDto) {
            return null;
        }

        $searchableProperties = $crudDto->getSearchFields();
        $query = $request->query->get('query');
        $defaultSort = $crudDto->getDefaultSort();
        $customSort = $request->query->get('sort', []);
        $appliedFilters = $request->query->get('filters', []);

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

        return $this->entityFactory->create($crudDto->getEntityFqcn(), $request->query->get('entityId'), $crudDto->getEntityPermission());
    }
}
