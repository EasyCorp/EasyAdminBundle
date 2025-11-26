<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class UserMenu
{
    private function __construct(private readonly UserMenuDto $dto)
    {
    }

    public static function new(): self
    {
        $dto = new UserMenuDto();

        return new self($dto);
    }

    public function displayUserName(bool $isDisplayed = true): self
    {
        $this->dto->setDisplayName($isDisplayed);

        return $this;
    }

    public function displayUserAvatar(bool $isDisplayed = true): self
    {
        $this->dto->setDisplayAvatar($isDisplayed);

        return $this;
    }

    public function setName(?string $name): self
    {
        $this->dto->setName($name);

        return $this;
    }

    public function setAvatarUrl(?string $url): self
    {
        $this->dto->setAvatarUrl($url);

        return $this;
    }

    public function setGravatarEmail(string $emailAddress): self
    {
        $hash = md5(strtolower(trim($emailAddress)));
        $this->dto->setAvatarUrl(sprintf('https://www.gravatar.com/avatar/%s', $hash));

        return $this;
    }

    /**
     * @param MenuItemInterface[] $items
     */
    public function addMenuItems(array $items): self
    {
        $itemsDto = array_map(
            static fn (MenuItemInterface $item): MenuItemDto => $item->getAsDto(),
            $items
        );

        $this->dto->setItems(array_merge($itemsDto, $this->dto->getItems()));

        return $this;
    }

    /**
     * @param MenuItemInterface[] $items
     */
    public function setMenuItems(array $items): self
    {
        $itemsDto = array_map(
            static fn (MenuItemInterface $item): MenuItemDto => $item->getAsDto(),
            $items
        );

        $this->dto->setItems($itemsDto);

        return $this;
    }

    public function getAsDto(): UserMenuDto
    {
        return $this->dto;
    }
}
