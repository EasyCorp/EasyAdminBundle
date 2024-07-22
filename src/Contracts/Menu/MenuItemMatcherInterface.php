<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemMatcherInterface
{
    /**
     * Given the full list of menu items and the current request, this method
     * must find the currently selected item (if any) and update its status
     * to mark it as selected.
     *
     * @param MenuItemDto[] $menuItems
     *
     * @return MenuItemDto[]
     */
    public function markSelectedMenuItem(array $menuItems, Request $request): array;
}
