<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Builder;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\ItemCollectionBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\MenuItemInterface;
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
    /** @var MenuItemInterface[] */
    private $builtMenuItems;
    /** @var MenuItemInterface[] */
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
     * @param MenuItemInterface $menuItem
     */
    public function addItem($menuItem): ItemCollectionBuilderInterface
    {
        $this->menuItems[] = $menuItem;
        $this->resetBuiltMenuItems();

        return $this;
    }

    /**
     * @param MenuItemInterface[] $menuItems
     * @return ItemCollectionBuilderInterface
     */
    public function setItems(array $menuItems): ItemCollectionBuilderInterface
    {
        $this->menuItems = $menuItems;
        $this->resetBuiltMenuItems();

        return $this;
    }

    /**
     * @return MenuItemInterface[]
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

        foreach ($this->menuItems as $i => $item) {
            if (false === $this->authChecker->isGranted($item->getPermission())) {
                continue;
            }

            $subItems = [];
            foreach ($item->getSubItems() as $j => $subItem) {
                if (false === $this->authChecker->isGranted($subItem->getPermission())) {
                    continue;
                }

                $subItems[] = $this->buildMenuItem($subItem, [], $i, $j, $translationDomain, $dashboardRouteName);
            }

            $builtItem = $this->buildMenuItem($item, $subItems, $i, -1, $translationDomain, $dashboardRouteName);

            $this->builtMenuItems[] = $builtItem;
        }

        $this->isBuilt = true;
    }

    private function buildMenuItem(MenuItemInterface $item, array $subItems, int $index, int $subIndex, string $translationDomain, string $dashboardRouteName): MenuItemInterface
    {
        $label = $this->translator->trans($item->getLabel(), [], $translationDomain);
        $url = $this->generateMenuItemUrl($item, $dashboardRouteName, $index, $subIndex);

        return MenuItem::build($item->getType(), $index, $subIndex, $label, $item->getIcon(), $url, $item->getPermission(), $item->getCssClass(), $item->getLinkRel(), $item->getLinkTarget(), $subItems);
    }

    private function generateMenuItemUrl(MenuItemInterface $menuItem, string $dashboardRouteName, int $index, int $subIndex): string
    {
        switch ($menuItem->getType()) {
            case self::TYPE_URL:
                return $menuItem->getLinkUrl();

            case self::TYPE_DASHBOARD:
                return $this->urlGenerator->generate($dashboardRouteName);

            case self::TYPE_ROUTE:
                // add the index and subIndex query parameters to display the selected menu item
                $menuParameters = ['menuIndex' => $index, 'submenuIndex' => $subIndex];
                $routeParameters = array_merge($menuParameters, $menuItem->getRouteParameters());

                return $this->urlGenerator->generate($menuItem->getRouteName(), $routeParameters);

            case self::TYPE_CRUD:
                // add the index and subIndex query parameters to display the selected menu item
                $menuParameters = ['menuIndex' => $index, 'submenuIndex' => $subIndex];
                $routeParameters = array_merge($menuParameters, $menuItem->getRouteParameters());

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
