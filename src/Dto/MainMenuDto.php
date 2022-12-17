<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class MainMenuDto
{
    private array $items;

    /**
     * @param MenuItemDto[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return MenuItemDto[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @deprecated Don't use this method; the selected menu item is now detected automatically using
     *              the Request data instead of having to deal with menuIndex/submenuIndex query params
     */
    public function isSelected(MenuItemDto $menuItemDto): bool
    {
        return $menuItemDto->isSelected();
    }

    /** @deprecated Don't use this method; the expanded menu item is now detected automatically using
     *              the Request data instead of having to deal with menuIndex/submenuIndex query params
     */
    public function isExpanded(MenuItemDto $menuItemDto): bool
    {
        return $menuItemDto->isExpanded();
    }
}
