<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class UserMenuDto
{
    use PropertyModifierTrait;

    private $displayName;
    private $displayAvatar;
    private $name;
    private $avatarUrl;
    private $items;

    public function __construct(bool $displayName, bool $displayAvatar, ?string $name, ?string $avatarUrl, array $items)
    {
        $this->displayName = $displayName;
        $this->displayAvatar = $displayAvatar;
        $this->name = $name;
        $this->avatarUrl = $avatarUrl;
        $this->items = $items;
    }

    public function isNameDisplayed(): bool
    {
        return $this->displayName;
    }

    public function isAvatarDisplayed(): bool
    {
        return $this->displayAvatar;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
