<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

/**
 * A utility class used to simplify the creation of the menu items.
 */
final class MenuItemBuilder
{
    private $type;
    private $label;
    private $icon;
    private $permission;
    private $subItems;
    private $routeName;
    private $routeParameters;
    private $url;
    private $cssClass;
    private $linkRel;
    private $linkTarget;

    public function __construct(?string $label, ?string $icon)
    {
        $this->label = $label ?? '';
        $this->icon = $icon ?? '';
        $this->subItems = [];
        $this->cssClass = '';
        $this->linkRel = '';
        $this->linkTarget = '_self';
    }

    public function entity(string $controllerClass, array $routeParameters = []): self
    {
        $this->type = MenuItem::TYPE_ENTITY;
        $this->routeParameters = array_merge([
            'crud' => $controllerClass,
            'page' => 'index',
        ], $routeParameters);

        return $this;
    }

    // TODO: update this method name to backendIndex() or dashboardIndex()
    public function homepage(): self
    {
        $this->type = MenuItem::TYPE_HOMEPAGE;

        return $this;
    }

    public function route(string $name, array $parameters = []): self
    {
        $this->type = MenuItem::TYPE_ROUTE;
        $this->routeName = $name;
        $this->routeParameters = $parameters;

        return $this;
    }

    public function section(): self
    {
        $this->type = MenuItem::TYPE_SECTION;

        return $this;
    }

    public function subMenu(MenuItemBuilder ...$items): self
    {
        $this->type = MenuItem::TYPE_SUBMENU;
        $this->subItems = $items;

        return $this;
    }

    public function url(string $url): self
    {
        $this->type = MenuItem::TYPE_URL;
        $this->url = $url;

        return $this;
    }

    public function cssClass(string $cssClass): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function linkRel(string $rel): self
    {
        $this->linkRel = $rel;

        return $this;
    }

    public function linkTarget(string $target): self
    {
        $this->linkTarget = $target;

        return $this;
    }

    public function permission(string $role): self
    {
        $this->permission = $role;

        return $this;
    }

    public function __debugInfo()
    {
        return get_object_vars($this);
    }
}
