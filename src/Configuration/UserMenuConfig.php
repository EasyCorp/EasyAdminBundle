<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\UserMenuContext;

final class UserMenuConfig
{
    private $isNameDisplayed = true;
    private $isAvatarDisplayed = true;
    private $name;
    private $avatarUrl;
    /** @var MenuItem[] */
    private $menuItems = [];

    public static function new(): self
    {
        return new self();
    }

    public function isNameDisplayed(bool $isDisplayed): self
    {
        $this->isNameDisplayed = $isDisplayed;

        return $this;
    }

    public function isAvatarDisplayed(bool $isDisplayed): self
    {
        $this->isAvatarDisplayed = $isDisplayed;

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
     * @param \EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem[] $items
     */
    public function addMenuItems(array $items): self
    {
        $this->menuItems = array_merge($items, $this->menuItems);

        return $this;
    }

    /**
     * @param \EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem[] $items
     */
    public function setMenuItems(array $items): self
    {
        $this->menuItems = $items;

        return $this;
    }

    public function getAsValueObject(): UserMenuContext
    {
        return new UserMenuContext($this->isNameDisplayed, $this->isAvatarDisplayed, $this->name, $this->avatarUrl, $this->menuItems);
    }
}
