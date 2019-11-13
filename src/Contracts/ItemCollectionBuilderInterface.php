<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts;

interface ItemCollectionBuilderInterface
{
    public function addItem($menuItem): self;

    public function setItems(array $menuItems): self;

    public function build(): array;
}
