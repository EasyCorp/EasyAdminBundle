<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

trait CommonMenuItemOptionsTrait
{
    private $type;
    private $label;
    private $icon;
    private $cssClass = '';
    private $permission;
    private $translationDomain;
    private $translationParameters = [];

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
}
