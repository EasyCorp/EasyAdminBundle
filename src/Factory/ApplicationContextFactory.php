<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\TemplateRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApplicationContextFactory
{
    private $tokenStorage;
    private $menuFactory;

    public function __construct(?TokenStorageInterface $tokenStorage, MenuFactory $menuFactory)
    {
        $this->tokenStorage = $tokenStorage;
        $this->menuFactory = $menuFactory;
    }

    public function create(Request $request, DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): ApplicationContext
    {
        $crudAction = $request->query->get('crudAction');
        $validPageNames = [CrudConfig::PAGE_INDEX, CrudConfig::PAGE_DETAIL, CrudConfig::PAGE_EDIT, CrudConfig::PAGE_NEW];
        $pageName = \in_array($crudAction, $validPageNames) ? $crudAction : null;

        $dashboardDto = $this->getDashboardDto($request, $dashboardController);
        $assetDto = $this->getAssetDto($dashboardController, $crudController);
        $actionDtoCollection = $this->getActions($dashboardController, $crudController, $pageName);

        $crudDto = $this->getCrudDto($dashboardController, $crudController, $actionDtoCollection, $crudAction, $pageName);
        $searchDto = $this->getSearchDto($request, $crudDto);
        $i18nDto = $this->getI18nDto($request, $dashboardDto, $crudDto);
        $templateRegistry = $this->getTemplateRegistry($dashboardController, $crudDto);
        $user = $this->getUser($this->tokenStorage);

        return new ApplicationContext($request, $user, $i18nDto, $dashboardDto, $dashboardController, $assetDto, $crudDto, $searchDto, $this->menuFactory, $templateRegistry);
    }

    private function getDashboardDto(Request $request, DashboardControllerInterface $dashboardControllerInstance): DashboardDto
    {
        $currentRouteName = $request->attributes->get('_route');

        return $dashboardControllerInstance
            ->configureDashboard()
            ->getAsDto()
            ->with(['routeName' => $currentRouteName]);
    }

    private function getAssetDto(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController): AssetDto
    {
        $defaultAssetConfig = $dashboardController->configureAssets();

        if (null === $crudController) {
            return $defaultAssetConfig->getAsDto();
        }

        return $crudController->configureAssets($defaultAssetConfig)->getAsDto();
    }

    private function getCrudDto(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ActionDtoCollection $actionDtoCollection, ?string $crudAction, ?string $pageName): ?CrudDto
    {
        if (null === $crudController) {
            return null;
        }

        $defaultCrudConfig = $dashboardController->configureCrud();

        return $crudController->configureCrud($defaultCrudConfig)
            ->getAsDto()
            ->with([
                'actions' => $actionDtoCollection,
                'actionName' => $crudAction,
                'pageName' => $pageName,
            ]);
    }

    private function getActions(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, ?string $pageName): ActionDtoCollection
    {
        if (null === $crudController || null === $pageName) {
            return ActionDtoCollection::new();
        }

        $defaultActionConfig = $dashboardController->configureActions();

        return $crudController->configureActions($defaultActionConfig)->getAsDto($pageName);
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
        $filters = $crudDto->getFilters();

        return new SearchDto($request, $searchableProperties, $query, $defaultSort, $customSort, $filters);
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
