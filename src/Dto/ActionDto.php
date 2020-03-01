<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;

final class ActionDto
{
    use PropertyAccessorTrait;
    use PropertyModifierTrait;

    private $type;
    private $name;
    private $label;
    private $icon;
    private $cssClass;
    private $htmlElement;
    private $htmlAttributes;
    private $linkUrl;
    private $templatePath;
    private $permission;
    private $crudActionName;
    private $routeName;
    private $routeParameters;
    private $translationDomain;
    private $translationParameters;
    private $displayCallable;

    public function __construct(string $type, string $name, ?string $label, ?string $icon, ?string $cssClass, string $htmlElement, array $htmlAttributes, ?string $templatePath, ?string $permission, ?string $crudActionName, ?string $routeName, ?array $routeParameters, ?string $translationDomain, array $translationParameters, callable $displayCallable)
    {
        $this->type = $type;
        $this->name = $name;
        $this->label = $label;
        $this->icon = $icon;
        $this->cssClass = $cssClass;
        $this->htmlElement = $htmlElement;
        $this->htmlAttributes = $htmlAttributes;
        $this->templatePath = $templatePath;
        $this->permission = $permission;
        $this->crudActionName = $crudActionName;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
        $this->translationDomain = $translationDomain;
        $this->translationParameters = $translationParameters;
        $this->displayCallable = $displayCallable;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isEntityAction(): bool
    {
        return $this->type === Action::TYPE_ENTITY;
    }

    public function isGlobalAction(): bool
    {
        return $this->type === Action::TYPE_GLOBAL;
    }

    public function isBatchAction(): bool
    {
        return $this->type === Action::TYPE_BATCH;
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

    public function getHtmlElement(): string
    {
        return $this->htmlElement;
    }

    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    public function getTemplate(): ?string
    {
        return $this->templatePath;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getLinkUrl(): string
    {
        return $this->linkUrl;
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

    public function shouldBeDisplayedFor(EntityDto $entityDto): bool
    {
        return \call_user_func($this->displayCallable, $entityDto->getInstance());
    }
}
