<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FiltersDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AdminContextFactory
{
    private $tokenStorage;
    private $menuFactory;
    private $crudControllers;

    public function __construct(?TokenStorageInterface $tokenStorage, MenuFactory $menuFactory, iterable $crudControllers)
    {
        $this->tokenStorage = $tokenStorage;
        $this->menuFactory = $menuFactory;
        $this->crudControllers = CrudControllerRegistry::new($crudControllers);
    }

    public function create(Request $request, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AdminContext
    {
        $crudAction = $request->query->get('crudAction');
        $validPageNames = [Crud::PAGE_INDEX, Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW];
        $pageName = \in_array($crudAction, $validPageNames, true) ? $crudAction : null;

        $dashboardDto = $this->getDashboardDto($request, $dashboardController);
        $assetDto = $this->getAssetDto($dashboardController, $crudController);
        $actionsDto = $this->getActions($dashboardController, $crudController, $pageName);
        $filtersDto = $this->getFilters($dashboardController, $crudController);

        $crudDto = $this->getCrudDto($this->crudControllers, $dashboardController, $crudController, $actionsDto, $filtersDto, $crudAction, $pageName);
        $searchDto = $this->getSearchDto($request, $crudDto);
        $i18nDto = $this->getI18nDto($request, $dashboardDto, $crudDto);
        $templateRegistry = $this->getTemplateRegistry($dashboardController, $crudDto);
        $user = $this->getUser($this->tokenStorage);

        return new AdminContext($request, $user, $i18nDto, $this->crudControllers, $dashboardDto, $dashboardController, $assetDto, $crudDto, $searchDto, $this->menuFactory, $templateRegistry);
    }

    private function getDashboardDto(Request $request, DashboardControllerInterface $dashboardControllerInstance): DashboardDto
    {
        $currentRouteName = $request->attributes->get('_route');

        return $dashboardControllerInstance
            ->configureDashboard()
            ->getAsDto()
            ->with(['routeName' => $currentRouteName]);
    }

    private function getAssetDto(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AssetsDto
    {
        $defaultAssets = $dashboardController->configureAssets();

        if (null === $crudController) {
            return $defaultAssets->getAsDto();
        }

        return $crudController->configureAssets($defaultAssets)->getAsDto();
    }

    private function getCrudDto(CrudControllerRegistry $crudControllerRegistry, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ActionsDto $actionsDto, FiltersDto $filtersDto, ?string $crudAction, ?string $pageName): ?CrudDto
    {
        if (null === $crudController) {
            return null;
        }

        $defaultCrud = $dashboardController->configureCrud();
        $crudDto = $crudController->configureCrud($defaultCrud)->getAsDto();

        $entityFqcn = $crudControllerRegistry->getEntityFqcnByControllerFqcn(\get_class($crudController));
        $entityClassName = basename(str_replace('\\', '/', $entityFqcn));
        $entityName = empty($entityClassName) ? 'Undefined' : $entityClassName;

        return $crudDto->with([
            'actions' => $actionsDto,
            'filters' => $filtersDto,
            'actionName' => $crudAction,
            'entityFqcn' => $entityFqcn,
            'labelInSingular' => $crudDto->getLabelInSingular() ?? $entityName,
            'labelInPlural' => $crudDto->getLabelInPlural() ?? $entityName,
            'pageName' => $pageName,
        ]);
    }

    private function getActions(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ?string $pageName): ActionsDto
    {
        if (null === $crudController || null === $pageName) {
            return (new ActionsDto())->setPageName($pageName ?? Crud::PAGE_INDEX);
        }

        $defaultActions = $dashboardController->configureActions();

        return $crudController->configureActions($defaultActions)->getAsDto($pageName);
    }

    private function getFilters(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): FiltersDto
    {
        if (null === $crudController) {
            return new FiltersDto([]);
        }

        $defaultFilters = $dashboardController->configureFilters();

        return $crudController->configureFilters($defaultFilters)->getAsDto();
    }

    private function getTemplateRegistry(DashboardControllerInterface $dashboardController, ?CrudDto $crudDto): TemplateRegistry
    {
        $templateRegistry = TemplateRegistry::new();

        $defaultCrudDto = $dashboardController->configureCrud()->getAsDto();
        $templateRegistry->addTemplates($defaultCrudDto->get('overriddenTemplates'));

        if (null !== $crudDto) {
            $templateRegistry->addTemplates($crudDto->get('overriddenTemplates'));
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
            $translationParameters['%entity_label_singular%'] = $crudDto->getLabelInSingular();
            $translationParameters['%entity_label_plural%'] = $crudDto->getLabelInPlural();
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
    // MIT License - (c) Fabien Potencier <fabien@symfony.com>
    private function getUser(?TokenStorageInterface $tokenStorage): ?UserInterface
    {
        if (null === $tokenStorage || !$token = $tokenStorage->getToken()) {
            return null;
        }

        $user = $token->getUser();

        return \is_object($user) ? $user : null;
    }
}
