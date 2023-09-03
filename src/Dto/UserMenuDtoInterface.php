<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface UserMenuDtoInterface
{
    public function isNameDisplayed(): bool;

    public function setDisplayName(bool $isDisplayed): void;

    public function isAvatarDisplayed(): bool;

    public function setDisplayAvatar(bool $isDisplayed): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getAvatarUrl(): ?string;

    public function setAvatarUrl(?string $url): void;

    public function getItems(): array;

    /**
     * When configuring the application, you are passed an array of
     * MenuItemInterface objects; after building the user menu contents,
     * this method is called with MenuItemDto objects.
     *
     * @param MenuItemInterface[]|MenuItemDto[] $items
     */
    public function setItems(array $items): void;
}
