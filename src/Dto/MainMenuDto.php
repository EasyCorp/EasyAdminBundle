<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class MainMenuDto implements MainMenuDtoInterface
{
    private array $items;

    /**
     * @param MenuItemDtoInterface[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function isSelected(MenuItemDtoInterface $menuItemDto): bool
    {
        return $menuItemDto->isSelected();
    }

    public function isExpanded(MenuItemDtoInterface $menuItemDto): bool
    {
        return $menuItemDto->isExpanded();
    }
}
