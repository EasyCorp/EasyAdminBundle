<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToExitImpersonation()
 */
final class ExitImpersonationMenuItem
{
    use MenuItemTrait;

    public function __construct(string $label, ?string $icon)
    {
        $this->type = MenuFactory::ITEM_TYPE_EXIT_IMPERSONATION;
        $this->label = $label;
        $this->icon = $icon;
    }
}
