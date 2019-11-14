<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Builder;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\ItemCollectionBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Routing\EntityRouter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MenuItemBuilder implements ItemCollectionBuilderInterface
{
    public const TYPE_CRUD = 'crud';
    public const TYPE_DASHBOARD = 'dashboard';
    public const TYPE_EXIT_IMPERSONATION = 'exit_impersonation';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_ROUTE = 'route';
    public const TYPE_SECTION = 'section';
    public const TYPE_SUBMENU = 'submenu';
    public const TYPE_URL = 'url';

    private $isBuilt;
    /** @var MenuItemDto[] */
    private $builtMenuItems;
    /** @var MenuItem[] */
    private $menuItems;
    private $authChecker;
    private $urlGenerator;
    private $translator;
    private $logoutUrlGenerator;
    private $applicationContextProvider;

    public function __construct(ApplicationContextProvider $applicationContextProvider, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator, LogoutUrlGenerator $logoutUrlGenerator)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->authChecker = $authChecker;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->logoutUrlGenerator = $logoutUrlGenerator;
    }

    /**
     * @param MenuItem $menuItem
     */
    public function addItem($menuItem): ItemCollectionBuilderInterface
    {
        $this->menuItems[] = $menuItem;
        $this->resetBuiltMenuItems();

        return $this;
    }

    /**
     * @param MenuItem[] $menuItems
     * @return ItemCollectionBuilderInterface
     */
    public function setItems(array $menuItems): ItemCollectionBuilderInterface
    {
        $this->menuItems = $menuItems;
        $this->resetBuiltMenuItems();

        return $this;
    }

    /**
     * @return \EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto[]
     */
    public function build(): array
    {
        if (!$this->isBuilt) {
            $this->buildMenuItems();
            $this->isBuilt = true;
        }

        return $this->builtMenuItems;
    }

    private function resetBuiltMenuItems(): void
    {
        $this->builtMenuItems = [];
        $this->isBuilt = false;
    }

    private function buildMenuItems(): void
    {
        $this->resetBuiltMenuItems();

        $applicationContext = $this->applicationContextProvider->getContext();
        $translationDomain = $applicationContext->getConfig()->getTranslationDomain();
        $dashboardRouteName = $applicationContext->getDashboardRouteName();

        foreach ($this->menuItems as $i => $menuItem) {
            $menuItemContext = $menuItem->getAsDto();
            if (false === $this->authChecker->isGranted($menuItemContext->getPermission())) {
                continue;
            }

            $subItems = [];
            /** @var MenuItem $menuSubItemConfig */
            foreach ($menuItemContext->getSubItems() as $j => $menuSubItemConfig) {
                $menuSubItemContext = $menuSubItemConfig->getAsDto();
                if (false === $this->authChecker->isGranted($menuSubItemContext->getPermission())) {
                    continue;
                }

                $subItems[] = $this->buildMenuItem($menuSubItemContext, [], $i, $j, $translationDomain, $dashboardRouteName);
            }

            $builtItem = $this->buildMenuItem($menuItemContext, $subItems, $i, -1, $translationDomain, $dashboardRouteName);

            $this->builtMenuItems[] = $builtItem;
        }

        $this->isBuilt = true;
    }

    private function buildMenuItem(MenuItemDto $menuItemContext, array $subItemsContext, int $index, int $subIndex, string $translationDomain, string $dashboardRouteName): MenuItemDto
    {
        $label = $this->translator->trans($menuItemContext->getLabel(), [], $translationDomain);
        $url = $this->generateMenuItemUrl($menuItemContext, $dashboardRouteName, $index, $subIndex);

        return $menuItemContext->withProperties([
            'index' => $index,
            'subIndex' => $subIndex,
            'label' => $label,
            'linkUrl' => $url,
            'subItems' => $subItemsContext,
        ]);
    }

    private function generateMenuItemUrl(MenuItemDto $menuItemContext, string $dashboardRouteName, int $index, int $subIndex): string
    {
        switch ($menuItemContext->getType()) {
            case self::TYPE_URL:
                return $menuItemContext->getLinkUrl();

            case self::TYPE_DASHBOARD:
                return $this->urlGenerator->generate($dashboardRouteName);

            case self::TYPE_ROUTE:
                // add the index and subIndex query parameters to display the selected menu item
                $menuParameters = ['menuIndex' => $index, 'submenuIndex' => $subIndex];
                $routeParameters = array_merge($menuParameters, $menuItemContext->getRouteParameters());

                return $this->urlGenerator->generate($menuItemContext->getRouteName(), $routeParameters);

            case self::TYPE_CRUD:
                // add the index and subIndex query parameters to display the selected menu item
                $menuParameters = ['menuIndex' => $index, 'submenuIndex' => $subIndex];
                $routeParameters = array_merge($menuParameters, $menuItemContext->getRouteParameters());

                return $this->urlGenerator->generate($dashboardRouteName, $routeParameters);

            case self::TYPE_LOGOUT:
                return $this->logoutUrlGenerator->getLogoutPath();

            case self::TYPE_EXIT_IMPERSONATION:
                // the switch parameter name can be changed, but this code assumes it's always
                // the default one because Symfony doesn't provide a generic exitImpersonationUrlGenerator
                return '?_switch_user=_exit';

            case self::TYPE_SECTION:
                return '#';

            default:
                return '';
        }
    }
}
