<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemDtoInterface
{
    public const TYPE_CRUD = 'crud';

    public const TYPE_URL = 'url';

    public const TYPE_SECTION = 'section';

    public const TYPE_EXIT_IMPERSONATION = 'exit_impersonation';

    public const TYPE_DASHBOARD = 'dashboard';

    public const TYPE_LOGOUT = 'logout';

    public const TYPE_SUBMENU = 'submenu';

    public const TYPE_ROUTE = 'route';

    public function getType(): string;

    public function setType(string $type): void;

    /** @deprecated This was used in the past to get the selected menu item
     *              Now the active menu item is detected automatically via the Request data
     */
    public function getIndex(): int;

    /** @deprecated This was used in the past to set the selected menu item
     *              Now the active menu item is detected automatically via the Request data
     */
    public function setIndex(int $index): void;

    /** @deprecated This was used in the past to get the selected menu subitem
     *              Now the active menu item is detected automatically via the Request data
     */
    public function getSubIndex(): int;

    /** @deprecated This was used in the past to set the selected menu subitem
     *              Now the active menu item is detected automatically via the Request data
     */
    public function setSubIndex(int $subIndex): void;

    /**
     * @return bool Returns true when this menu item is the selected one
     */
    public function isSelected(): bool;

    public function setSelected(bool $isSelected): void;

    /**
     * @return bool Returns true when any of its subitems is selected
     */
    public function isExpanded(): bool;

    public function setExpanded(bool $isExpanded): void;

    public function getLabel(): TranslatableInterface|string;

    public function setLabel(TranslatableInterface|string $label): void;

    public function getIcon(): ?string;

    public function setIcon(?string $icon): void;

    public function getLinkUrl(): ?string;

    public function setLinkUrl(?string $linkUrl): void;

    public function getRouteName(): ?string;

    public function setRouteName(?string $routeName): void;

    public function getRouteParameters(): ?array;

    public function setRouteParameter(string $parameterName, mixed $parameterValue): void;

    public function setRouteParameters(?array $routeParameters): void;

    public function getPermission(): ?string;

    public function setPermission(?string $permission): void;

    public function getCssClass(): string;

    public function setCssClass(string $cssClass): void;

    public function getLinkRel(): string;

    public function setLinkRel(string $linkRel): void;

    public function getLinkTarget(): string;

    public function setLinkTarget(string $linkTarget): void;

    public function getTranslationParameters(): array;

    public function setTranslationParameters(array $translationParameters): void;

    public function getBadge(): ?MenuItemBadgeDtoInterface;

    public function setBadge(mixed $content, string $style): void;

    /**
     * @return MenuItemDtoInterface[]
     */
    public function getSubItems(): array;

    /**
     * @param MenuItemDtoInterface[] $subItems
     */
    public function setSubItems(array $subItems): void;

    public function hasSubItems(): bool;

    public function isMenuSection(): bool;
}
