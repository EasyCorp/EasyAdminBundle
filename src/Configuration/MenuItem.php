<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\MenuItemInterface;

final class MenuItem implements MenuItemInterface
{
    public const TYPE_CRUD = 'crud';
    public const TYPE_DASHBOARD = 'dashboard';
    public const TYPE_EXIT_IMPERSONATION = 'exit_impersonation';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_ROUTE = 'route';
    public const TYPE_SECTION = 'section';
    public const TYPE_SUBMENU = 'submenu';
    public const TYPE_URL = 'url';

    private $type;
    private $index;
    private $subIndex;
    private $label;
    private $icon;
    private $cssClass = '';
    private $permission;
    private $routeName;
    private $routeParameters;
    private $linkUrl = '';
    private $linkRel = '';
    private $linkTarget = '_self';
    /** @var MenuItemInterface[] */
    private $subItems = [];

    /**
     * @internal Don't use this constructor; use the named constructors
     */
    private function __construct()
    {
    }

    public static function crud(string $label, string $icon, string $crudControllerFqcn, array $routeParameters = []): self
    {
        $menuItem = new self();
        $menuItem->type = self::TYPE_CRUD;
        $menuItem->label = $label;
        $menuItem->icon = $icon;
        $menuItem->routeParameters = array_merge([
            'crud' => $crudControllerFqcn,
            'page' => 'index',
        ], $routeParameters);

        return $menuItem;
    }

    public static function dashboardIndex(string $label, string $icon): self
    {
        $menuItem = new self();
        $menuItem->type = self::TYPE_DASHBOARD;
        $menuItem->label = $label;
        $menuItem->icon = $icon;

        return $menuItem;
    }

    public static function exitImpersonation(string $label, string $icon): self
    {
        $menuItem = new self();
        $menuItem->type = self::TYPE_EXIT_IMPERSONATION;
        $menuItem->label = $label;
        $menuItem->icon = $icon;

        return $menuItem;
    }

    public static function logout(string $label, string $icon): self
    {
        $menuItem = new self();
        $menuItem->type = self::TYPE_LOGOUT;
        $menuItem->label = $label;
        $menuItem->icon = $icon;

        return $menuItem;
    }

    public static function route(string $name, array $parameters = []): self
    {
        $menuItem = new self();
        $menuItem->type = self::TYPE_ROUTE;
        $menuItem->routeName = $name;
        $menuItem->routeParameters = $parameters;

        return $menuItem;
    }

    public static function section(string $label = null, string $icon = null): self
    {
        $menuItem = new self();
        $menuItem->type = self::TYPE_SECTION;
        $menuItem->label = $label;
        $menuItem->icon = $icon;

        return $menuItem;
    }

    public static function subMenu(string $label, string $icon, array $submenuItems): self
    {
        $menuItem = new self();
        $menuItem->type = self::TYPE_SUBMENU;
        $menuItem->label = $label;
        $menuItem->icon = $icon;
        $menuItem->subItems = $submenuItems;

        return $menuItem;
    }

    public static function url(string $label, string $icon, string $url): self
    {
        $menuItem = new self();
        $menuItem->type = self::TYPE_URL;
        $menuItem->label = $label;
        $menuItem->icon = $icon;
        $menuItem->linkUrl = $url;

        return $menuItem;
    }

    public static function build(string $type, int $index, int $subIndex, string $label, string $icon, string $linkUrl, ?string $permission, string $cssClass, string $linkRel, string $linkTarget, array $subItems): MenuItemInterface
    {
        $menuItem = new self();

        $menuItem->type = $type;
        $menuItem->index = $index;
        $menuItem->subIndex = $subIndex;
        $menuItem->label = $label;
        $menuItem->icon = $icon;
        $menuItem->linkUrl = $linkUrl;
        $menuItem->permission = $permission;
        $menuItem->cssClass = $cssClass;
        $menuItem->linkRel = $linkRel;
        $menuItem->linkTarget = $linkTarget;
        $menuItem->subItems = $subItems;

        return $menuItem;
    }

    public function setCssClass(string $cssClass): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function setLinkRel(string $rel): self
    {
        $this->linkRel = $rel;

        return $this;
    }

    public function setLinkTarget(string $target): self
    {
        $this->linkTarget = $target;

        return $this;
    }

    public function setPermission(string $role): self
    {
        $this->permission = $role;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getSubIndex(): int
    {
        return $this->subIndex;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getLinkUrl(): string
    {
        return $this->linkUrl;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters ?? [];
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function getLinkRel(): string
    {
        return $this->linkRel;
    }

    public function getLinkTarget(): string
    {
        return $this->linkTarget;
    }

    public function getSubItems(): array
    {
        return $this->subItems;
    }

    public function isSelected(?int $selectedIndex, ?int $selectedSubIndex = null): bool
    {
        if (null === $selectedSubIndex) {
            return $this->getIndex() === $selectedIndex;
        }

        return $this->getIndex() === $selectedIndex && $this->getSubIndex() === $selectedSubIndex;
    }

    public function isExpanded(?int $selectedIndex, ?int $selectedSubIndex): bool
    {
        return $this->isSelected($selectedIndex) && -1 !== $selectedSubIndex;
    }

    public function hasSubItems(): bool
    {
        return self::TYPE_SUBMENU === $this->type && count($this->subItems) > 0;
    }

    public function isMenuSection(): bool
    {
        return self::TYPE_SECTION === $this->type;
    }
}
