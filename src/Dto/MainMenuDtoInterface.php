<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MainMenuDtoInterface
{
    /**
     * @return MenuItemDto[]
     */
    public function getItems(): array;

    /** @deprecated Don't use this method; the selected menu item is now detected automatically using
     *              the Request data instead of having to deal with menuIndex/submenuIndex query params
     */
    public function isSelected(MenuItemDto $menuItemDto): bool;

    /** @deprecated Don't use this method; the expanded menu item is now detected automatically using
     *              the Request data instead of having to deal with menuIndex/submenuIndex query params
     */
    public function isExpanded(MenuItemDto $menuItemDto): bool;
}
