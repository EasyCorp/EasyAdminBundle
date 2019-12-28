<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class ActionDto
{
    use PropertyAccessorTrait;
    use PropertyModifierTrait;

    private $name;
    private $label;
    private $icon;
    private $cssClass;
    private $linkUrl;
    private $linkTarget;
    private $linkTitleAttribute;
    private $templatePath;
    private $permission;
    private $crudActionName;
    private $routeName;
    private $routeParameters;
    private $translationDomain;
    private $translationParameters;

    public function __construct(string $name, ?string $label, ?string $icon, ?string $cssClass, ?string $linkTitleAttribute, string $linkTarget, ?string $templatePath, ?string $permission, ?string $crudActionName, ?string $routeName, ?array $routeParameters, ?string $translationDomain, array $translationParameters)
    {
        $this->name = $name;
        $this->label = $label;
        $this->icon = $icon;
        $this->cssClass = $cssClass;
        $this->linkTitleAttribute = $linkTitleAttribute;
        $this->linkTarget = $linkTarget;
        $this->templatePath = $templatePath;
        $this->permission = $permission;
        $this->crudActionName = $crudActionName;
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

    public function getLinkTarget(): string
    {
        return $this->linkTarget;
    }

    public function getLinkTitleAttribute(): ?string
    {
        return $this->linkTitleAttribute;
    }

    public function getTemplate(): ?string
    {
        return $this->templatePath;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getCrudActionName(): ?string
    {
        return $this->crudActionName;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    public function getTranslationParams(): array
    {
        return $this->translationParameters;
    }
}
