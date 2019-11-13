<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

final class ActionContext
{
    private $name;
    private $label;
    private $icon;
    private $url;
    private $cssClass;
    private $htmlTitle;
    private $target;
    private $template;
    private $permission;
    private $methodName;
    private $routeName;
    private $routeParameters;
    private $translationDomain;
    private $translationParameters;

    public function __construct(string $name, ?string $label, ?string $icon, ?string $cssClass, ?string $htmlTitle, string $target, ?string $template, ?string $permission, ?string $methodName, ?string $routeName, ?array $routeParameters, ?string $translationDomain, array $translationParameters)
    {
        $this->name = $name;
        $this->label = $label;
        $this->icon = $icon;
        $this->cssClass = $cssClass;
        $this->htmlTitle = $htmlTitle;
        $this->target = $target;
        $this->template = $template;
        $this->permission = $permission;
        $this->methodName = $methodName;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
        $this->translationDomain = $translationDomain;
        $this->translationParameters = $translationParameters;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function getHtmlTitle(): ?string
    {
        return $this->htmlTitle;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getMethodName(): ?string
    {
        return $this->methodName;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getRouteParameters(): ?array
    {
        return $this->routeParameters;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    public function withProperties(array $properties): self
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if (!property_exists($this, $propertyName)) {
                throw new \InvalidArgumentException(sprintf('The "%s" option is not a valid action context option name. Valid option names are: %s', $propertyName, implode(', ', get_object_vars($this))));
            }

            $this->{$propertyName} = $propertyValue;
        }

        return $this;
    }
}
