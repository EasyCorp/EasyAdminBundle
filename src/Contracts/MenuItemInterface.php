<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemInterface
{
    public static function build(string $type, int $index, int $subIndex, string $label, string $icon, string $url, ?string $permission, string $cssClass, string $linkRel, string $linkTarget, array $subItems): self;

    public function isSelected(?int $selectedIndex, ?int $selectedSubIndex = null): bool;

    public function isExpanded(?int $selectedIndex, ?int $selectedSubIndex): bool;

    public function isMenuSection(): bool;

    public function hasSubItems(): bool;

    public function getType(): string;

    public function getIndex(): int;

    public function getSubIndex(): int;

    public function getLabel(): string;

    public function getIcon(): string;

    public function getRouteName(): ?string;

    public function getRouteParameters(): array;

    public function getPermission(): ?string;

    public function getCssClass(): string;

    public function getLinkUrl(): string;

    public function getLinkRel(): string;

    public function getLinkTarget(): string;

    /** @return MenuItemInterface[] */
    public function getSubItems(): array;
}
