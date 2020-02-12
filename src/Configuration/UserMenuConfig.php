<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;

final class UserMenuConfig
{
    private $displayName = true;
    private $displayAvatar = true;
    private $name;
    private $avatarUrl;
    /** @var MenuItem[] */
    private $menuItems = [];

    public static function new(): self
    {
        return new self();
    }

    public function displayUserName(bool $isDisplayed = true): self
    {
        $this->displayName = $isDisplayed;

        return $this;
    }

    public function displayUserAvatar(bool $isDisplayed = true): self
    {
        $this->displayAvatar = $isDisplayed;

        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setAvatarUrl(?string $url): self
    {
        $this->avatarUrl = $url;

        return $this;
    }

    public function setGravatarEmail(string $emailAddress): self
    {
        $hash = md5(strtolower(trim($emailAddress)));
        $this->avatarUrl = sprintf('https://www.gravatar.com/avatar/%s', $hash);

        return $this;
    }

    /**
     * @param MenuItem[] $items
     */
    public function addMenuItems(array $items): self
    {
        $this->menuItems = array_merge($items, $this->menuItems);

        return $this;
    }

    /**
     * @param MenuItem[] $items
     */
    public function setMenuItems(array $items): self
    {
        $this->menuItems = $items;

        return $this;
    }

    public function getAsDto(): UserMenuDto
    {
        return new UserMenuDto($this->displayName, $this->displayAvatar, $this->name, $this->avatarUrl, $this->menuItems);
    }
}
