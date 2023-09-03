<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class UserMenuDto implements UserMenuDtoInterface
{
    private bool $displayName = true;
    private bool $displayAvatar = true;
    private ?string $name = null;
    private ?string $avatarUrl = null;
    /** @var MenuItemDtoInterface[] */
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

    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
