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
    private bool $logoutLinkDisabled = false;
    private ?string $name = null;
    private ?string $avatarUrl = null;
    /** @var array<MenuItemDto> */
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

    public function isLogoutLinkDisabled(): bool
    {
        return $this->logoutLinkDisabled;
    }

    public function setLogoutLinkDisabled(bool $disabled): void
    {
        $this->logoutLinkDisabled = $disabled;
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
     * @return array<MenuItemDto>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<MenuItemDto> $items
     */
    public function setItems(array $items): void
    {
        foreach ($items as $item) {
            if (!$item instanceof MenuItemDto && !$item instanceof MenuItemInterface) {
                throw new \InvalidArgumentException(sprintf('Expected an instance of "%s" or "%s".', MenuItemDto::class, MenuItemInterface::class));
            }
        }

        $this->items = $items;
    }
}
