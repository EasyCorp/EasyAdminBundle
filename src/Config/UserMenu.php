<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDtoInterface;

final class UserMenu implements UserMenuInterface
{
    private UserMenuDto $dto;

    private function __construct(UserMenuDtoInterface $userMenuDto)
    {
        $this->dto = $userMenuDto;
    }

    public static function new(): UserMenuInterface
    {
        $dto = new UserMenuDto();

        return new self($dto);
    }

    public function displayUserName(bool $isDisplayed = true): UserMenuInterface
    {
        $this->dto->setDisplayName($isDisplayed);

        return $this;
    }

    public function displayUserAvatar(bool $isDisplayed = true): UserMenuInterface
    {
        $this->dto->setDisplayAvatar($isDisplayed);

        return $this;
    }

    public function setName(?string $name): UserMenuInterface
    {
        $this->dto->setName($name);

        return $this;
    }

    public function setAvatarUrl(?string $url): UserMenuInterface
    {
        $this->dto->setAvatarUrl($url);

        return $this;
    }

    public function setGravatarEmail(string $emailAddress): UserMenuInterface
    {
        $hash = md5(strtolower(trim($emailAddress)));
        $this->dto->setAvatarUrl(sprintf('https://www.gravatar.com/avatar/%s', $hash));

        return $this;
    }

    public function addMenuItems(array $items): UserMenuInterface
    {
        $this->dto->setItems(array_merge($items, $this->dto->getItems()));

        return $this;
    }

    public function setMenuItems(array $items): UserMenuInterface
    {
        $this->dto->setItems($items);

        return $this;
    }

    public function getAsDto(): UserMenuDtoInterface
    {
        return $this->dto;
    }
}
