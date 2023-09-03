<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class MainMenuDto implements MainMenuDtoInterface
{
    private array $items;

    /**
     * @param MenuItemDto[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function isSelected(MenuItemDto $menuItemDto): bool
    {
        return $menuItemDto->isSelected();
    }

    public function isExpanded(MenuItemDto $menuItemDto): bool
    {
        return $menuItemDto->isExpanded();
    }
}
