<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class UserMenuDto
{
    private $displayName;
    private $displayAvatar;
    private $name;
    private $avatarUrl;
    /** @var MenuItem[] */
    private $items;

    public function __construct()
    {
        $this->displayAvatar = true;
        $this->displayName = true;
        $this->items = [];
    }

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

    /**
     * @return MenuItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param MenuItemDto[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
