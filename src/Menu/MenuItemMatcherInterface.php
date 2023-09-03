<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemMatcherInterface
{
    /**
     * @return bool Returns true when this menu item is the selected one
     */
    public function isSelected(MenuItemDtoInterface $menuItemDto): bool;

    /**
     * @return bool Returns true when any of the subitems of the menu item is selected
     */
    public function isExpanded(MenuItemDtoInterface $menuItemDto): bool;
}
