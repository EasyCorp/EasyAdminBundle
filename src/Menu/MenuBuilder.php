<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Routing\EntityRouter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MenuBuilder implements MenuBuilderInterface
{
    private $isBuilt;
    /** @var MenuItemInterface[] */
    private $builtItems;
    /** @var MenuItemInterface[] */
    private $items;
    private $authChecker;
    private $urlGenerator;
    private $translator;
    private $applicationContextProvider;

    public function __construct(AuthorizationCheckerInterface $authChecker, UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator, ApplicationContextProvider $applicationContextProvider)
    {
        $this->authChecker = $authChecker;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->applicationContextProvider = $applicationContextProvider;
    }

    public function addItem(MenuItemInterface $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return MenuItemInterface[]
     */
    public function build(): array
    {
        if (!$this->isBuilt) {
            $this->buildMenu();
            $this->isBuilt = true;
        }

        return $this->builtItems;
    }

    private function buildMenu(): void
    {
        $this->builtItems = [];
        $translationDomain = $this->getApplicationContext()->getTranslationDomain();
        $dashboardRouteName = $this->getApplicationContext()->getDashboardRouteName();

        foreach ($this->items as $i => $item) {
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

            $this->builtItems[] = $builtItem;
        }
    }

    private function buildMenuItem(MenuItemInterface $item, array $subItems, int $index, int $subIndex, string $translationDomain, string $dashboardRouteName): MenuItemInterface
    {
        $label = $this->translator->trans($item->getLabel(), [], $translationDomain);
        $url = $this->getUrl($item, $dashboardRouteName, $index, $subIndex);

        return MenuItem::build(
            $item->getType(),
            $index,
            $subIndex,
            $label,
            $item->getIcon(),
            $url,
            $item->getPermission(),
            $item->getCssClass(),
            $item->getLinkRel(),
            $item->getLinkTarget(),
            $subItems
        );
    }

    private function getUrl(MenuItemInterface $item, string $dashboardRouteName, int $index, int $subIndex): string
    {
        switch ($item->getType()) {
            case MenuItem::TYPE_URL:
                return $item->getLinkUrl();

            case MenuItem::TYPE_DASHBOARD:
                return $this->urlGenerator->generate($dashboardRouteName);

            case MenuItem::TYPE_ROUTE:
                // add the index and subIndex query parameters to display the selected menu item
                $menuParameters = ['menuIndex' => $index, 'submenuIndex' => $subIndex];
                $routeParameters = array_merge($menuParameters, $item->getRouteParameters());

                return $this->urlGenerator->generate($item->getRouteName(), $routeParameters);

            case MenuItem::TYPE_CRUD:
                // add the index and subIndex query parameters to display the selected menu item
                $menuParameters = ['menuIndex' => $index, 'submenuIndex' => $subIndex];
                $routeParameters = array_merge($menuParameters, $item->getRouteParameters());

                return $this->urlGenerator->generate($dashboardRouteName, $routeParameters);

            case MenuItem::TYPE_SECTION:
                return '#';

            default:
                return '';
        }
    }

    private function getApplicationContext(): ApplicationContext
    {
        return $this->applicationContextProvider->getContext();
    }
}
