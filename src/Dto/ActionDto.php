<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionDto
{
    private $type;
    private $name;
    private $label;
    private $icon;
    private $cssClass;
    private $htmlElement;
    private $htmlAttributes;
    private $linkUrl;
    private $templatePath;
    private $crudActionName;
    private $routeName;
    private $routeParameters;
    private $url;
    private $translationParameters;
    private $displayCallable;

    public function __construct()
    {
        $this->cssClass = '';
        $this->htmlAttributes = [];
        $this->routeParameters = [];
        $this->translationParameters = [];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isEntityAction(): bool
    {
        return Action::TYPE_ENTITY === $this->type;
    }

    public function isGlobalAction(): bool
    {
        return Action::TYPE_GLOBAL === $this->type;
    }

    public function isBatchAction(): bool
    {
        return Action::TYPE_BATCH === $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|false|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass): void
    {
        $this->cssClass = $cssClass;
    }

    public function getHtmlElement(): string
    {
        return $this->htmlElement;
    }

    public function setHtmlElement(string $htmlElement): void
    {
        $this->htmlElement = $htmlElement;
    }

    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    public function setHtmlAttributes(array $htmlAttributes): void
    {
        $this->htmlAttributes = $htmlAttributes;
    }

    public function setHtmlAttribute(string $attributeName, string $attributeValue): void
    {
        $this->htmlAttributes[$attributeName] = $attributeValue;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function setTemplatePath(string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    public function getLinkUrl(): string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(string $linkUrl): void
    {
        $this->linkUrl = $linkUrl;
    }

    public function getCrudActionName(): ?string
    {
        return $this->crudActionName;
    }

    public function setCrudActionName(string $crudActionName): void
    {
        $this->crudActionName = $crudActionName;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): void
    {
        $this->routeName = $routeName;
    }

    /**
     * @return array|callable
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * @param array|callable $routeParameters
     */
    public function setRouteParameters($routeParameters): void
    {
        $this->routeParameters = $routeParameters;
    }

    /**
     * @return string|callable
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string|callable $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    public function setTranslationParameters(array $translationParameters): void
    {
        $this->translationParameters = $translationParameters;
    }

    public function shouldBeDisplayedFor(EntityDto $entityDto): bool
    {
        return null === $this->displayCallable || \call_user_func($this->displayCallable, $entityDto->getInstance());
    }

    public function setDisplayCallable(callable $displayCallable): void
    {
        $this->displayCallable = $displayCallable;
    }

    /**
     * @internal
     */
    public function getAsConfigObject(): Action
    {
        $action = Action::new($this->name, $this->label, $this->icon);
        $action->setCssClass($this->cssClass);
        $action->setHtmlAttributes($this->htmlAttributes);
        $action->setTranslationParameters($this->translationParameters);

        if (null !== $this->templatePath) {
            $action->setTemplatePath($this->templatePath);
        }

        if ($this->isGlobalAction()) {
            $action->createAsGlobalAction();
        } elseif ($this->isBatchAction()) {
            $action->createAsBatchAction();
        }

        if ('a' === $this->htmlElement) {
            $action->displayAsLink();
        } else {
            $action->displayAsButton();
        }

        if (null !== $this->crudActionName) {
            $action->linkToCrudAction($this->crudActionName);
        }

        if (null !== $this->routeName) {
            $action->linkToRoute($this->routeName, $this->routeParameters);
        }

        if (null !== $this->displayCallable) {
            $action->displayIf($this->displayCallable);
        }

        return $action;
    }
}
