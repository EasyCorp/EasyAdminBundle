<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class UserMenuDto
{
    private bool $displayName = true;
    private bool $displayAvatar = true;
    private ?string $name = null;
    private ?string $avatarUrl = null;
    /** @var MenuItemDto[] */
    private array $items = [];

    public function isNameDisplayed(): bool
    {
        return $this->displayName;
    }

    public function setDisplayName(bool $isDisplayed): void
    {
        $this->displayName = $isDisplayed;
    }

    public function isAvatarDisplayed(): bool
    {
        return $this->displayAvatar;
    }

    public function setDisplayAvatar(bool $isDisplayed): void
    {
        $this->displayAvatar = $isDisplayed;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $url): void
    {
        $this->avatarUrl = $url;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * When configuring the application, you are passed an array of
     * MenuItemInterface objects; after building the user menu contents,
     * this method is called with MenuItemDto objects.
     *
     * @param MenuItemInterface[]|MenuItemDto[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
