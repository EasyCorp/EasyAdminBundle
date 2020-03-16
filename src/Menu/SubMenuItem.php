<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::submenu()
 */
final class SubMenuItem
{
    use CommonMenuItemOptionsTrait;
    private $subItems;

    public function __construct(string $label, ?string $icon)
    {
        $this->label = $label;
        $this->icon = $icon;
    }

    public function setSubItems(array $subItems): self
    {
        $this->subItems = $subItems;

        return $this;
    }

    public function getAsDto(): MenuItemDto
    {
        return new MenuItemDto(MenuFactory::ITEM_TYPE_SUBMENU, $this->label, $this->icon, $this->permission, $this->cssClass, null, null, null, null, null, $this->translationDomain, $this->translationParameters, $this->subItems);
    }
}
