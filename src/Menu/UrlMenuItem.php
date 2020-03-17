<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToUrl()
 */
final class UrlMenuItem
{
    use MenuItemTrait;

    public function __construct(string $label, ?string $icon, string $url)
    {
        $this->type = MenuFactory::ITEM_TYPE_URL;
        $this->label = $label;
        $this->icon = $icon;
        $this->linkUrl = $url;
    }
}
