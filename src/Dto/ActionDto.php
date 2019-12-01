<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class ActionDto
{
    use PropertyModifierTrait;

    private $name;
    private $label;
    private $icon;
    private $cssClass;
    private $linkUrl;
    private $linkTarget;
    private $linkTitleAttribute;
    private $template;
    private $permission;
    private $methodName;
    private $routeName;
    private $routeParameters;
    private $translationDomain;
    private $translationParameters;

    public function __construct(string $name, ?string $label, ?string $icon, ?string $cssClass, ?string $linkTitleAttribute, string $linkTarget, ?string $template, ?string $permission, ?string $methodName, ?string $routeName, ?array $routeParameters, ?string $translationDomain, array $translationParameters)
    {
        $this->name = $name;
        $this->label = $label;
        $this->icon = $icon;
        $this->cssClass = $cssClass;
        $this->linkTitleAttribute = $linkTitleAttribute;
        $this->linkTarget = $linkTarget;
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

    public function getLinkTitleAttribute(): ?string
    {
        return $this->linkTitleAttribute;
    }

    public function getLinkTarget(): string
    {
        return $this->linkTarget;
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

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }
}
