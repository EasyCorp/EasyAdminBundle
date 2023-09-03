<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenuInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuFactoryInterface
{
    /**
     * @param MenuItemInterface[] $menuItems
     */
    public function createMainMenu(array $menuItems): MainMenuDtoInterface;

    public function createUserMenu(UserMenuInterface $userMenu): UserMenuDtoInterface;
}
