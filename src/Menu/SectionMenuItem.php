<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::section()
 */
final class SectionMenuItem
{
    use CommonMenuItemOptionsTrait;

    public function __construct(string $label, ?string $icon)
    {
        $this->type = MenuFactory::ITEM_TYPE_SECTION;
        $this->label = $label;
        $this->icon = $icon;
    }

    public function getAsDto()
    {
        return new MenuItemDto(MenuFactory::ITEM_TYPE_SECTION, $this->label, $this->icon, $this->permission, $this->cssClass, null, null, null, '', '_self', $this->translationDomain, $this->translationParameters, null);
    }
}
