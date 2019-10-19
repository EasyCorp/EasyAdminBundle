<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemInterface
{
    public function getType(): string;
    public function getIndex(): int;
    public function getSubindex(): int;
    public function getLabel(): string;
    public function getIcon(): string;
    public function getUrl(): string;
    public function getPermission(): ?string;
    public function getCssClass(): string;
    public function getLinkRel(): string;
    public function getLinkTarget(): string;
    /** @return MenuItemInterface[] */
    public function getSubItems(): array;
}
