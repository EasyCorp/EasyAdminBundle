<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;

final class Action
{
    private $name;
    private $label;
    private $icon;
    private $cssClass;
    private $linkTitleAttribute;
    private $linkTarget = '_self';
    private $template = '@EasyAdmin/action.html.twig';
    private $permission;
    private $methodName;
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
        $this->template = $templatePath;

        return $this;
    }

    public function setPermission(string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    public function setMethodName(string $methodName): self
    {
        $this->methodName = $methodName;

        return $this;
    }

    public function setRoute(string $routeName, array $routeParameters = [])
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

    public function withProperties(array $properties): self
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if (!property_exists($this, $propertyName)) {
                throw new \InvalidArgumentException(sprintf('The "%s" option is not a valid action option name. Valid option names are: %s', $propertyName, implode(', ', get_object_vars($this))));
            }

            $this->{$propertyName} = $propertyValue;
        }

        return $this;
    }

    public function getAsDto()
    {
        if (null === $this->label && null === $this->icon) {
            throw new \InvalidArgumentException(sprintf('The label and icon of an action cannot be null at the same time. Either set the label, the icon or both.'));
        }

        if (null === $this->methodName && null === $this->routeName) {
            throw new \InvalidArgumentException(sprintf('The method name and the route name of an action cannot be null at the same time. Either set the method name or the route name for the action "%s".', $this->name));
        }

        return new ActionDto($this->name, $this->label, $this->icon, $this->cssClass, $this->linkTitleAttribute, $this->linkTarget, $this->template, $this->permission, $this->methodName, $this->routeName, $this->routeParameters, $this->translationDomain, $this->translationParameters);
    }
}
