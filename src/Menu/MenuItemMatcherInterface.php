<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemMatcherInterface
{
    /**
     * @return bool Returns true when this menu item is the selected one
     */
    public function isSelected(MenuItemDto $menuItemDto): bool;

    /**
     * @return bool Returns true when any of the subitems of the menu item is selected
     */
    public function isExpanded(MenuItemDto $menuItemDto): bool;
}
