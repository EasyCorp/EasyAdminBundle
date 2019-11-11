<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

final class ActionContext
{
    private $name;
    private $label;
    private $icon;
    private $cssClass;
    private $title;
    private $target;
    private $template;
    private $methodName;
    private $routeName;
    private $routeParameters;

    public function __construct(string $name, ?string $label, ?string $icon, ?string $cssClass, ?string $title, string $target, ?string $template, ?string $methodName, ?string $routeName, ?array $routeParameters)
    {
        $this->name = $name;
        $this->label = $label;
        $this->icon = $icon;
        $this->cssClass = $cssClass;
        $this->title = $title;
        $this->target = $target;
        $this->template = $template;
        $this->methodName = $methodName;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
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
}
