<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemInterface
{
    public function getAsDto(): MenuItemDto;
}
