<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

interface MenuItemInterface
{
    public function getAsDto(): MenuItemDto;
}
