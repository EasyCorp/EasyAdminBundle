<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class MainMenuDto
{
    /**
     * @param MenuItemDto[] $items
     */
    public function __construct(private array $items)
    {
    }

    /**
     * @return MenuItemDto[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
