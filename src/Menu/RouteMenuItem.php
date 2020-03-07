<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToRoute()
 */
final class RouteMenuItem
{
    use CommonMenuItemOptionsTrait;
    use LinkMenuItemOptionsTrait;
    private $routeName;
    private $routeParameters;

    public function __construct(string $label, ?string $icon, string $routeName, array $routeParameters)
    {
        $this->label = $label;
        $this->icon = $icon;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
    }

    public function getAsDto()
    {
        return new MenuItemDto(MenuFactory::ITEM_TYPE_ROUTE, $this->label, $this->icon, $this->permission, $this->cssClass, $this->routeName, $this->routeParameters, null, $this->linkRel, $this->linkTarget, $this->translationDomain, $this->translationParameters, null);
    }
}
