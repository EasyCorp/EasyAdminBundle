<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyModifierTrait;

final class Action
{
    use PropertyModifierTrait;

    private $name;
    private $label;
    private $icon;
    private $cssClass;
    private $linkTitleAttribute;
    private $linkTarget = '_self';
    private $templateName = 'crud/action';
    private $templatePath;
    private $permission;
    private $crudActionName;
    private $routeName;
    private $routeParameters;
    private $translationDomain;
    private $translationParameters = [];

    private function __construct()
    {
    }

    public function __toString()
    {
        return $this->name;
    }

    public static function new(string $name, ?string $label = null, ?string $icon = null): self
    {
        $actionConfig = new self();
        $actionConfig->name = $name;
        $actionConfig->label = $label;
        $actionConfig->icon = $icon;

        return $actionConfig;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setCssClass(string $cssClass): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function setLinkTitleAttribute(string $title): self
    {
        $this->linkTitleAttribute = $title;

        return $this;
    }

    public function setLinkTarget(string $target): self
    {
        $this->linkTarget = $target;

        return $this;
    }

    public function setTemplate(string $templatePath): self
    {
        $this->templatePath = $templatePath;

        return $this;
    }

    public function setPermission(string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    public function linkToCrudAction(string $crudActionName): self
    {
        $this->crudActionName = $crudActionName;

        return $this;
    }

    public function linkToRoute(string $routeName, array $routeParameters = [])
    {
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;

        return $this;
    }

    /**
     * If not defined, actions use the same domain as configured for the entire dashboard
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

    public function getAsDto(): ActionDto
    {
        if (null === $this->label && null === $this->icon) {
            throw new \InvalidArgumentException(sprintf('The label and icon of an action cannot be null at the same time. Either set the label, the icon or both.'));
        }

        if (null === $this->crudActionName && null === $this->routeName) {
            throw new \InvalidArgumentException(sprintf('The method name and the route name of an action cannot be null at the same time. Either set the method name or the route name for the action "%s".', $this->name));
        }

        return new ActionDto($this->name, $this->label, $this->icon, $this->cssClass, $this->linkTitleAttribute, $this->linkTarget, $this->templateName, $this->templatePath, $this->permission, $this->crudActionName, $this->routeName, $this->routeParameters, $this->translationDomain, $this->translationParameters);
    }
}
