<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::submenu()
 */
final class SubMenuItem
{
    use MenuItemTrait {
        setLinkRel as private;
        setLinkTarget as private;
    }

    public function __construct(string $label, ?string $icon)
    {
        $this->type = MenuFactory::ITEM_TYPE_SUBMENU;
        $this->label = $label;
        $this->icon = $icon;
    }

    public function setSubItems(array $subItems): self
    {
        $this->subItems = $subItems;

        return $this;
    }
}
