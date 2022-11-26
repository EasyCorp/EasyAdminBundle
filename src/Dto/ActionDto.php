<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionDto
{
    private ?string $type = null;
    private ?string $name = null;
    private TranslatableInterface|string|null $label = null;
    private ?string $icon = null;
    private string $cssClass = '';
    private ?string $htmlElement = null;
    private array $htmlAttributes = [];
    private ?string $linkUrl = null;
    private ?string $templatePath = null;
    private ?string $crudActionName = null;
    private ?string $routeName = null;
    private $routeParameters = [];
    /* @var callable|string|null */
    private $url;
    private array $translationParameters = [];
    private $displayCallable;

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

    public function getLabel(): TranslatableInterface|string|false|null
    {
        return $this->label;
    }

    public function setLabel(TranslatableInterface|string|false|null $label): void
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

    public function addHtmlAttributes(array $htmlAttributes): void
    {
        $this->htmlAttributes = array_merge($this->htmlAttributes, $htmlAttributes);
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
    public function getRouteParameters()/* : array|callable */
    {
        return $this->routeParameters;
    }

    public function setRouteParameters(/* @var array|callable */ $routeParameters): void
    {
        if (!\is_array($routeParameters) && !\is_callable($routeParameters)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$routeParameters',
                __METHOD__,
                '"array" or "callable"',
                \gettype($routeParameters)
            );
        }

        $this->routeParameters = $routeParameters;
    }

    /**
     * @return string|callable|null
     */
    public function getUrl()/* : string|callable */
    {
        return $this->url;
    }

    public function setUrl(/* @var string|callable */ $url): void
    {
        if (!\is_string($url) && !\is_callable($url)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$url',
                __METHOD__,
                '"string" or "callable"',
                \gettype($url)
            );
        }

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
        return null === $this->displayCallable || (bool) \call_user_func($this->displayCallable, $entityDto->getInstance());
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
