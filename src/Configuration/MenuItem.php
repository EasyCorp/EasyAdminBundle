<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Builder\MenuItemBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

final class MenuItem
{
    private $type;
    private $label;
    private $icon;
    private $cssClass = '';
    private $permission;
    private $routeName;
    private $routeParameters;
    private $linkUrl;
    private $linkRel = '';
    private $linkTarget = '_self';
    private $translationDomain;
    private $translationParameters = [];
    /** @var MenuItemInterface[] */
    private $subItems = [];

    /**
     * @internal Don't use this constructor; use the named constructors
     */
    private function __construct()
    {
    }

    public static function linkToCrud(string $label, ?string $icon, string $crudControllerFqcn, array $routeParameters = []): self
    {
        $menuItem = new self();
        $menuItem->type = MenuItemBuilder::TYPE_CRUD;
        $menuItem->label = $label;
        $menuItem->icon = $icon;
        $menuItem->routeParameters = array_merge([
            'crudController' => $crudControllerFqcn,
            'crudAction' => 'index',
        ], $routeParameters);

        return $menuItem;
    }

    public static function linktoDashboard(string $label, ?string $icon = null): self
    {
        $menuItem = new self();
        $menuItem->type = MenuItemBuilder::TYPE_DASHBOARD;
        $menuItem->label = $label;
        $menuItem->icon = $icon;

        return $menuItem;
    }

    public static function linkToExitImpersonation(string $label, ?string $icon = null): self
    {
        $menuItem = new self();
        $menuItem->type = MenuItemBuilder::TYPE_EXIT_IMPERSONATION;
        $menuItem->label = $label;
        $menuItem->icon = $icon;

        return $menuItem;
    }

    public static function linkToLogout(string $label, ?string $icon = null): self
    {
        $menuItem = new self();
        $menuItem->type = MenuItemBuilder::TYPE_LOGOUT;
        $menuItem->label = $label;
        $menuItem->icon = $icon;

        return $menuItem;
    }

    public static function linktoRoute(string $name, array $parameters = []): self
    {
        $menuItem = new self();
        $menuItem->type = MenuItemBuilder::TYPE_ROUTE;
        $menuItem->routeName = $name;
        $menuItem->routeParameters = $parameters;

        return $menuItem;
    }

    public static function linkToUrl(string $label, ?string $icon, string $url): self
    {
        $menuItem = new self();
        $menuItem->type = MenuItemBuilder::TYPE_URL;
        $menuItem->label = $label;
        $menuItem->icon = $icon;
        $menuItem->linkUrl = $url;

        return $menuItem;
    }

    public static function section(string $label = null, ?string $icon = null): self
    {
        $menuItem = new self();
        $menuItem->type = MenuItemBuilder::TYPE_SECTION;
        $menuItem->label = $label;
        $menuItem->icon = $icon;

        return $menuItem;
    }

    public static function subMenu(string $label, ?string $icon, array $submenuItems): self
    {
        $menuItem = new self();
        $menuItem->type = MenuItemBuilder::TYPE_SUBMENU;
        $menuItem->label = $label;
        $menuItem->icon = $icon;
        $menuItem->subItems = $submenuItems;

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

    /**
     * If not defined, menu items use the same domain as configured for the entire dashboard
     */
    public function setTranslationDomain(string $domain): self
    {
        $this->translationDomain = $domain;

        return $this;
    }

    public function setTranslationParameters(string $parameters): self
    {
        $this->translationParameters = $parameters;

        return $this;
    }

    public function getAsDto()
    {
        if (empty($this->label) && null === $this->icon) {
            throw new \InvalidArgumentException(sprintf('The label and icon of an action cannot be empty/null at the same time. Either set the label to a non-empty value, or set the icon or both.'));
        }

        return new MenuItemDto($this->type, $this->label, $this->icon, $this->permission, $this->cssClass, $this->routeName, $this->routeParameters, $this->linkUrl, $this->linkRel, $this->linkTarget, $this->translationDomain, $this->translationParameters, $this->subItems);
    }
}
