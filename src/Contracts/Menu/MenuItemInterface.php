<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemInterface
{
    public function getAsDto(): MenuItemDtoInterface;
}
