<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToRoute()
 */
final class RouteMenuItem
{
    use MenuItemTrait;

    public function __construct(string $label, ?string $icon, string $routeName, array $routeParameters)
    {
        $this->type = MenuFactory::ITEM_TYPE_ROUTE;
        $this->label = $label;
        $this->icon = $icon;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
    }
}
