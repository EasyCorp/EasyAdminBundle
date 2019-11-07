<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

interface MenuBuilderInterface
{
    public function addItem(MenuItemInterface $item): void;

    /** @return MenuItemInterface[] */
    public function build(): array;
}
