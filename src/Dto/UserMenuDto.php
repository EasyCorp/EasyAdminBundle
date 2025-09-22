<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class UserMenuDto
{
    private bool $displayName = true;
    private bool $displayAvatar = true;
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
            if (!$item instanceof MenuItemDto) {
                trigger_deprecation(
                    'easycorp/easyadmin-bundle',
                    '4.25.0',
                    'Argument "%s" for "%s" must be one of type: %s. Passing type %s will cause an error in 5.0.0.',
                    '$items',
                    __METHOD__,
                    '"array<MenuItemDto>"',
                    '"array<MenuItemInterface>"'
                );
                break;
            }
        }

        $this->items = $items;
    }
}
