<?php
namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;

interface MenuFactoryInterface
{
    public function createMainMenu(array $menuItems, int $selectedIndex, int $selectedSubIndex): MainMenuDto;
    public function createUserMenu(UserMenu $userMenu): UserMenuDto;
}
