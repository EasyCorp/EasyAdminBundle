<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Builder;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\UserMenuConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Configuration\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;

final class MenuBuilder
{
    private $menuItemBuilder;

    public function __construct(MenuItemBuilder $menuItemBuilder)
    {
        $this->menuItemBuilder = $menuItemBuilder;
    }

    /**
     * @param MenuItemInterface[] $menuItems
     */
    public function buildMainMenu(array $menuItems, int $selectedIndex, int $selectedSubIndex): MainMenuDto
    {
        $builtMainMenuItems = $this->menuItemBuilder->setItems($menuItems)->build();

        return new MainMenuDto($builtMainMenuItems, $selectedIndex, $selectedSubIndex);
    }

    public function buildUserMenu(UserMenuConfig $userMenuConfig): UserMenuDto
    {
        $userMenuDto = $userMenuConfig->getAsDto();
        $builtUserMenuItems = $this->menuItemBuilder->setItems($userMenuDto->getItems())->build();

        return $userMenuDto->with([
            'items' => $builtUserMenuItems,
        ]);
    }
}
