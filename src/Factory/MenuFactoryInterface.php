<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuFactoryInterface
{
    /**
     * @param MenuItemInterface[] $menuItems
     */
    public function createMainMenu(array $menuItems): MainMenuDto;

    public function createUserMenu(UserMenu $userMenu): UserMenuDto;
}
