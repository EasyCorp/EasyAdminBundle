<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkTo*()
 */
final class GenericMenuItem
{
    use CommonMenuItemOptionsTrait;
    use LinkMenuItemOptionsTrait;

    public function __construct(string $type, string $label, ?string $icon)
    {
        $this->type = $type;
        $this->label = $label;
        $this->icon = $icon;
    }

    public function getAsDto(): MenuItemDto
    {
        return new MenuItemDto($this->type, $this->label, $this->icon, $this->permission, $this->cssClass, null, null, null, $this->linkRel, $this->linkTarget, $this->translationDomain, $this->translationParameters, null);
    }
}
