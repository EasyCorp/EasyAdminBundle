<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

interface MenuBuilderInterface
{
    public function addItem(MenuItemInterface $item): self;

    /**
     * @param MenuItemInterface[] $items
     */
    public function setItems(array $items): self;

    /** @return MenuItemInterface[] */
    public function build(): array;
}
