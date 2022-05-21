<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @see EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToUrl()
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class UrlMenuItem implements MenuItemInterface
{
    use MenuItemTrait;

    public function __construct(TranslatableInterface|string $label, ?string $icon, string $url)
    {
        $this->dto = new MenuItemDto();

        $this->dto->setType(MenuItemDto::TYPE_URL);
        $this->dto->setLabel($label);
        $this->dto->setIcon($icon);
        $this->dto->setLinkUrl($url);
    }
}
