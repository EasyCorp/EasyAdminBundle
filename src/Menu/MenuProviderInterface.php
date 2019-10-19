<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

interface MenuProviderInterface
{
    public function addItem(MenuItemBuilder $item): void;

    /** @return MenuItemInterface[] */
    public function getItems(): array;

    public function isSelectedItem(MenuItemInterface $item): bool;

    public function isSelectedSubItem(MenuItemInterface $item): bool;

    public function isItemSubMenuExpanded(MenuItemInterface $item): bool;
}
