<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToDashboard()
 */
final class DashboardMenuItem
{
    use MenuItemTrait;

    public function __construct(string $label, ?string $icon)
    {
        $this->type = MenuFactory::ITEM_TYPE_DASHBOARD;
        $this->label = $label;
        $this->icon = $icon;
    }
}
