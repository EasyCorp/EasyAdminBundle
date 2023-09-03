<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface UserMenuInterface
{
    public static function new(): UserMenuInterface;

    public function displayUserName(bool $isDisplayed = true): UserMenuInterface;

    public function displayUserAvatar(bool $isDisplayed = true): UserMenuInterface;

    public function setName(?string $name): UserMenuInterface;

    public function setAvatarUrl(?string $url): UserMenuInterface;

    public function setGravatarEmail(string $emailAddress): UserMenuInterface;

    /**
     * @param MenuItemInterface[] $items
     */
    public function addMenuItems(array $items): UserMenuInterface;

    /**
     * @param MenuItemInterface[] $items
     */
    public function setMenuItems(array $items): UserMenuInterface;

    public function getAsDto(): UserMenuDtoInterface;
}
