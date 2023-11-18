<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;

/**
 * @author IndyDevGuy <contact@indydevguy.com>
 */
interface MenuFactoryInterface
{
    /**
     * Creates the main menu by processing the given list of menu items (e.g. to mark
     * the currently selected menu item, to filter out the items that can't be displayed
     * to current user because of security permissions).
     *
     * @param MenuItemInterface[] $menuItems
     */
    public function createMainMenu(array $menuItems): MainMenuDto;

    /**
     * Creates the menu of user actions displayed as a dropdown at the top
     * of the page and associated to the name of the currently logged in user.
     * This method processes the given menu items e.g. to filter out the items
     * that can't be displayed to current user because of security permissions.
     */
    public function createUserMenu(UserMenu $userMenu): UserMenuDto;
}
