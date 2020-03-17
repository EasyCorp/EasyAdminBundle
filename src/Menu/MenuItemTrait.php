<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

trait MenuItemTrait
{
    private $type;
    private $label;
    private $icon;
    private $cssClass = '';
    private $permission;
    private $translationDomain;
    private $translationParameters = [];
    private $routeName;
    private $routeParameters;
    private $linkUrl;
    private $linkRel = '';
    private $linkTarget = '_self';
    private $subItems;

    public function setCssClass(string $cssClass): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function setPermission(string $role): self
    {
        $this->permission = $role;

        return $this;
    }

    /**
     * If not defined, menu items use the same domain as configured for the entire dashboard.
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

    public function getAsDto(): MenuItemDto
    {
        return new MenuItemDto($this->type, $this->label, $this->icon, $this->permission, $this->cssClass, $this->routeName, $this->routeParameters, $this->linkUrl, $this->linkRel, $this->linkTarget, $this->translationDomain, $this->translationParameters, $this->subItems);
    }
}
