<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use function Symfony\Component\String\u;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class MenuFactory
{
    private $adminContextProvider;
    private $dashboardRegistry;
    private $authChecker;
    private $translator;
    private $urlGenerator;
    private $logoutUrlGenerator;
    private $crudUrlGenerator;

    public function __construct(AdminContextProvider $adminContextProvider, DashboardControllerRegistry $dashboardRegistry, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator, LogoutUrlGenerator $logoutUrlGenerator, CrudUrlGenerator $crudRouter)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->dashboardRegistry = $dashboardRegistry;
        $this->authChecker = $authChecker;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->logoutUrlGenerator = $logoutUrlGenerator;
        $this->crudUrlGenerator = $crudRouter;
    }

    /**
     * @param MenuItem[] $menuItems
     */
    public function createMainMenu(array $menuItems, int $selectedIndex, int $selectedSubIndex): MainMenuDto
    {
        return new MainMenuDto($this->buildMenuItems($menuItems), $selectedIndex, $selectedSubIndex);
    }

    public function createUserMenu(UserMenu $userMenu): UserMenuDto
    {
        $userMenuDto = $userMenu->getAsDto();
        $builtUserMenuItems = $this->buildMenuItems($userMenuDto->getItems());
        $userMenuDto->setItems($builtUserMenuItems);

        return $userMenuDto;
    }

    /**
     * @param MenuItem[] $menuItems
     *
     * @return MenuItemDto[]
     */
    private function buildMenuItems(array $menuItems): array
    {
        $adminContext = $this->adminContextProvider->getContext();
        $translationDomain = $adminContext->getI18n()->getTranslationDomain();
        $dashboardRouteName = $adminContext->getDashboardRouteName();
        $dashboardControllerFqcn = $adminContext->getDashboardControllerFqcn();
        $adminContextId = $this->dashboardRegistry->getContextIdByControllerFqcn($dashboardControllerFqcn);

        $builtItems = [];
        /** @var MenuItemInterface $menuItem */
        foreach ($menuItems as $i => $menuItem) {
            $menuItemDto = $menuItem->getAsDto();
            if (false === $this->authChecker->isGranted(Permission::EA_VIEW_MENU_ITEM, $menuItemDto)) {
                continue;
            }

            $subItems = [];
            foreach ($menuItemDto->getSubItems() as $j => $menuSubItemDto) {
                if (false === $this->authChecker->isGranted(Permission::EA_VIEW_MENU_ITEM, $menuSubItemDto)) {
                    continue;
                }

                $subItems[] = $this->buildMenuItem($menuSubItemDto, [], $i, $j, $translationDomain, $dashboardRouteName, $adminContextId);
            }

            $builtItems[] = $this->buildMenuItem($menuItemDto, $subItems, $i, -1, $translationDomain, $dashboardRouteName, $adminContextId);
        }

        return $builtItems;
    }

    private function buildMenuItem(MenuItemDto $menuItemDto, array $subItems, int $index, int $subIndex, string $translationDomain, string $dashboardRouteName, string $adminContextId): MenuItemDto
    {
        $uLabel = u($menuItemDto->getLabel());
        // labels with this prefix are considered internal and must be translated
        // with 'EasyAdminBundle' translation domain, regardlesss of the backend domain
        if ($uLabel->startsWith('__ea__')) {
            $uLabel = $uLabel->after('__ea__');
            $translationDomain = 'EasyAdminBundle';
        }
        $label = $uLabel->toString();
        $translatedLabel = empty($label) ? $label : $this->translator->trans($label, $menuItemDto->getTranslationParameters(), $translationDomain);

        $url = $this->generateMenuItemUrl($menuItemDto, $dashboardRouteName, $adminContextId, $index, $subIndex);

        $menuItemDto->setIndex($index);
        $menuItemDto->setSubIndex($subIndex);
        $menuItemDto->setLabel($translatedLabel);
        $menuItemDto->setLinkUrl($url);
        $menuItemDto->setSubItems($subItems);

        return $menuItemDto;
    }

    private function generateMenuItemUrl(MenuItemDto $menuItemDto, string $dashboardRouteName, string $adminContextId, int $index, int $subIndex): string
    {
        $menuItemType = $menuItemDto->getType();

        if (MenuItemDto::TYPE_CRUD === $menuItemType) {
            $routeParameters = $menuItemDto->getRouteParameters();

            // remove all existing query params to avoid keeping search queries, filters and pagination
            $urlBuilder = $this->crudUrlGenerator->build()->unsetAll();

            // add the index and subIndex query parameters to display the selected menu item
            $urlBuilder->set('menuIndex', $index)->set('submenuIndex', $subIndex);

            $urlBuilder->setAll($routeParameters);

            $entityFqcn = $routeParameters['entityFqcn'] ?? null;
            $crudControllerFqcn = $routeParameters['crudControllerFqcn'] ?? null;
            if (null === $entityFqcn && null === $crudControllerFqcn) {
                throw new \RuntimeException(sprintf('The CRUD menu item with label "%s" must define either the entity FQCN (using the third constructor argument) or the CRUD Controller FQCN (using the "setController()" method).', $menuItemDto->getLabel()));
            }

            // 1. if CRUD controller is defined, use it...
            if (null !== $crudControllerFqcn) {
                $urlBuilder->setController($crudControllerFqcn);
            // 2. ...otherwise, find the CRUD controller from the entityFqcn
            } else {
                $crudControllers = $this->adminContextProvider->getContext()->getCrudControllers();
                if (null === $controllerFqcn = $crudControllers->findCrudFqcnByEntityFqcn($entityFqcn)) {
                    throw new \RuntimeException(sprintf('Unable to find the controller related to the "%s" Entity; did you forget to extend "%s"?', $entityFqcn, AbstractCrudController::class));
                }

                $urlBuilder->setController($controllerFqcn);
                $urlBuilder->unset('entityFqcn');
            }

            return $urlBuilder->generateUrl();
        }

        if (MenuItemDto::TYPE_DASHBOARD === $menuItemType) {
            return $this->urlGenerator->generate($dashboardRouteName, ['menuIndex' => $index, 'submenuIndex' => $subIndex]);
        }

        if (MenuItemDto::TYPE_ROUTE === $menuItemType) {
            return $this->urlGenerator->generate($menuItemDto->getRouteName(), array_merge(
                ['menuIndex' => $index, 'submenuIndex' => $subIndex, 'eaContext' => $adminContextId],
                $menuItemDto->getRouteParameters()
            ));
        }

        if (MenuItemDto::TYPE_SECTION === $menuItemType) {
            return '#';
        }

        if (MenuItemDto::TYPE_URL === $menuItemType) {
            return $menuItemDto->getLinkUrl();
        }

        if (MenuItemDto::TYPE_LOGOUT === $menuItemType) {
            return $this->logoutUrlGenerator->getLogoutPath();
        }

        if (MenuItemDto::TYPE_EXIT_IMPERSONATION === $menuItemType) {
            // the switch parameter name can be changed, but this code assumes it's always
            // the default one because Symfony doesn't provide a generic exitImpersonationUrlGenerator
            return '?_switch_user=_exit';
        }

        return '';
    }
}
