<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToUrl()
 */
final class UrlMenuItem
{
    use CommonMenuItemOptionsTrait;
    use LinkMenuItemOptionsTrait;
    private $linkUrl;

    public function __construct(string $label, ?string $icon, string $url)
    {
        $this->label = $label;
        $this->icon = $icon;
        $this->linkUrl = $url;
    }

    public function getAsDto()
    {
        return new MenuItemDto(MenuFactory::ITEM_TYPE_URL, $this->label, $this->icon, $this->permission, $this->cssClass, null, null, $this->linkUrl, $this->linkRel, $this->linkTarget, $this->translationDomain, $this->translationParameters, null);
    }
}
