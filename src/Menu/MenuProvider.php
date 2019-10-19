<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Routing\EntityRouter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MenuProvider implements MenuProviderInterface
{
    private $isBuilt;
    /** @var MenuItemCollection */
    private $items;
    /** @var MenuItemBuilder[] */
    private $itemBuilders;
    private $authChecker;
    private $urlGenerator;
    private $translator;
    private $entityRouter;
    private $applicationContextProvider;

    public function __construct(AuthorizationCheckerInterface $authChecker, UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator, EntityRouter $entityRouter, ApplicationContextProvider $applicationContextProvider)
    {
        $this->authChecker = $authChecker;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->entityRouter = $entityRouter;
        $this->applicationContextProvider = $applicationContextProvider;
    }

    public function addItem(MenuItemBuilder $item): void
    {
        $this->itemBuilders[] = $item;
    }

    /**
     * @return MenuItemInterface[]
     */
    public function getItems(): array
    {
        if (!$this->isBuilt) {
            $this->build();
            $this->isBuilt = true;
        }

        return $this->items;
    }

    public function isSelectedItem(MenuItemInterface $item): bool
    {
        $selectedMenuIndex = $this->getApplicationContext()->getRequest()->query->getInt('menuIndex', -1);

        return $item->getIndex() === $selectedMenuIndex;
    }

    public function isSelectedSubItem(MenuItemInterface $item): bool
    {
        $selectedSubmenuIndex = $this->getApplicationContext()->getRequest()->query->getInt('submenuIndex', -1);

        return $this->isSelectedItem($item) && $item->getSubindex() === $selectedSubmenuIndex;
    }

    public function isItemSubMenuExpanded(MenuItemInterface $item): bool
    {
        $selectedSubmenuIndex = $this->getApplicationContext()->getRequest()->query->getInt('submenuIndex', -1);

        return $this->isSelectedItem($item) && -1 !== $selectedSubmenuIndex;
    }

    private function getApplicationContext(): ApplicationContext
    {
        return $this->applicationContextProvider->getContext();
    }

    private function build(): void
    {
        $this->items = [];
        $translationDomain = $this->getApplicationContext()->getDashboard()->getConfig()->getTranslationDomain();
        $dashboardRouteName = $this->getApplicationContext()->getDashboardRouteName();

        foreach ($this->itemBuilders as $i => $itemBuilder) {
            $itemConfig = $itemBuilder->__debugInfo();

            if (false === $this->authChecker->isGranted($itemConfig['permission'])) {
                continue;
            }

            $subItems = [];
            foreach ($itemConfig['subItems'] as $j => $subItemBuilder) {
                $subItemConfig = $subItemBuilder->__debugInfo();
                if (false === $this->authChecker->isGranted($subItemConfig['permission'])) {
                    continue;
                }

                $subItems[] = $this->buildMenuItem($subItemConfig, $translationDomain, $dashboardRouteName, $i, $j);
            }

            $itemConfig['subItems'] = $subItems;

            $item = $this->buildMenuItem($itemConfig, $translationDomain, $dashboardRouteName, $i, -1);

            $this->items[] = $item;
        }
    }

    private function buildMenuItem(array $itemConfig, string $translationDomain, string $dashboardRouteName, int $index, int $subIndex): MenuItemInterface
    {
        $label = $this->translator->trans($this->getLabel($itemConfig), [], $translationDomain);
        $url = $this->getUrl($itemConfig, $dashboardRouteName, $index, $subIndex);

        return new MenuItem(
            $itemConfig['type'],
            $index,
            $subIndex,
            $label,
            $itemConfig['icon'],
            $url,
            $itemConfig['permission'],
            $itemConfig['cssClass'],
            $itemConfig['linkRel'],
            $itemConfig['linkTarget'],
            $itemConfig['subItems']
        );
    }

    private function getLabel(array $itemConfig)
    {
        return $itemConfig['label'] ?? $this->getApplicationContext()->getEntityConfig()->getName();
    }

    private function getUrl(array $itemConfig, string $dashboardRouteName, int $index, int $subIndex): string
    {
        switch ($itemConfig['type']) {
            case MenuItem::TYPE_URL:
                return $itemConfig['url'];

            case MenuItem::TYPE_HOMEPAGE:
                return $this->urlGenerator->generate($dashboardRouteName);

            case MenuItem::TYPE_ROUTE:
                // add the index and subIndex query parameters to display the selected menu item
                $menuParameters = ['menuIndex' => $index, 'submenuIndex' => $subIndex];
                $routeParameters = array_merge($menuParameters, $itemConfig['routeParameters']);

                return $this->urlGenerator->generate($itemConfig['routeName'], $routeParameters);

            case MenuItem::TYPE_ENTITY:
                // TODO: path('easyadmin', { entity: item.entity, action: 'list' }|merge(menu_params)|merge(item.params))
                return $this->entityRouter->generate($itemConfig['entityController'], $itemConfig['entityAction'], $itemConfig['entityParameters']);

            case MenuItem::TYPE_SECTION:
                return '#';

            default:
                return '';
        }
    }
}
