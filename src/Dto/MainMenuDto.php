<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class MainMenuDto
{
    private $items;
    private $selectedIndex;
    private $selectedSubIndex;

    /**
     * @param MenuItemDto[] $items
     */
    public function __construct(array $items, int $selectedIndex, int $selectedSubIndex)
    {
        $this->items = $items;
        $this->selectedIndex = $selectedIndex;
        $this->selectedSubIndex = $selectedSubIndex;
    }

    /**
     * @return MenuItemDto[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isSelected(MenuItemDto $menuItemDto): bool
    {
        return $menuItemDto->getIndex() === $this->selectedIndex && $menuItemDto->getSubIndex() === $this->selectedSubIndex;
    }

    public function isExpanded(MenuItemDto $menuItemDto): bool
    {
        return $menuItemDto->getIndex() === $this->selectedIndex && -1 !== $this->selectedSubIndex;
    }
}
