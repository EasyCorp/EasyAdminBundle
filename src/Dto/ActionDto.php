<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonElement;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonStyle;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonType;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonVariant;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionDto
{
    private ?string $type = null;
    private ?string $name = null;
    /** @var TranslatableInterface|string|(callable(object): string)|false|null */
    private mixed $label = null;
    private ?string $icon = null;
    private string $cssClass = '';
    private string $addedCssClass = '';
    /** @var array<string, string> */
    private array $htmlAttributes = [];
    private ?string $linkUrl = null;
    private ?string $templatePath = null;
    private ?string $crudActionName = null;
    private ?string $routeName = null;
    /** @var array<string, mixed>|callable */
    private $routeParameters = [];
    /** @var callable|string|null */
    private $url;
    /** @var array<string, mixed> */
    private array $translationParameters = [];
    /** @var callable|null */
    private $displayCallable;
    private ButtonElement $htmlElement = ButtonElement::Button;
    private ButtonType $buttonType = ButtonType::Submit;
    private ButtonVariant $variant = ButtonVariant::Default;
    private ButtonStyle $style = ButtonStyle::Solid;

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

    // A utility method for Twig templates that deal with actions and action groups
    public function isActionGroup(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isDynamicLabel(): bool
    {
        return \is_callable($this->label);
    }

    public function getLabel(): TranslatableInterface|string|callable|false|null
    {
        return $this->label;
    }

    /**
     * @param TranslatableInterface|string|(callable(object $entity): string)|false|null $label
     */
    public function setLabel(TranslatableInterface|string|callable|false|null $label): void
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
        return trim($this->cssClass);
    }

    public function setCssClass(string $cssClass): void
    {
        $this->cssClass = $cssClass;
    }

    public function getAddedCssClass(): string
    {
        return trim($this->addedCssClass);
    }

    public function setAddedCssClass(string $cssClass): void
    {
        $this->addedCssClass .= ' '.$cssClass;
    }

    public function getHtmlElement(): ButtonElement
    {
        return $this->htmlElement;
    }

    public function isRenderedAsButton(): bool
    {
        return ButtonElement::Button === $this->htmlElement;
    }

    public function isRenderedAsLink(): bool
    {
        return ButtonElement::A === $this->htmlElement;
    }

    public function isRenderedAsForm(): bool
    {
        return ButtonElement::Form === $this->htmlElement;
    }

    public function setHtmlElement(ButtonElement|string $htmlElement): void
    {
        if (\is_string($htmlElement)) {
            $validElementsAsStrings = array_map(static fn (ButtonElement $case): string => $case->value, ButtonElement::cases());
            $htmlElement = ButtonElement::tryFrom($htmlElement) ?? throw new \InvalidArgumentException(sprintf('The "%s" value is not valid for the "%s" option. Valid values are: %s', $htmlElement, '$htmlElement', implode(', ', $validElementsAsStrings)));
        }

        $this->htmlElement = $htmlElement;
    }

    public function getButtonType(): ButtonType
    {
        return $this->buttonType;
    }

    public function setButtonType(ButtonType $buttonType): void
    {
        $this->buttonType = $buttonType;
    }

    /**
     * @return array<string, string>
     */
    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    /**
     * @param array<string, string> $htmlAttributes
     */
    public function addHtmlAttributes(array $htmlAttributes): void
    {
        $this->htmlAttributes = array_merge($this->htmlAttributes, $htmlAttributes);
    }

    /**
     * @param array<string, string> $htmlAttributes
     */
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
     * @return array<string, mixed>|callable
     */
    public function getRouteParameters()/* : array|callable */
    {
        return $this->routeParameters;
    }

    /**
     * @param array<string, mixed>|callable $routeParameters
     */
    public function setRouteParameters($routeParameters): void
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string|callable $url
     */
    public function setUrl($url): void
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

    /**
     * @return array<string, mixed>
     */
    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    /**
     * @param array<string, mixed> $translationParameters
     */
    public function setTranslationParameters(array $translationParameters): void
    {
        $this->translationParameters = $translationParameters;
    }

    public function shouldBeDisplayedFor(EntityDto $entityDto): bool
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.9.4',
            'The "%s" method is deprecated and it will be removed in 5.0.0 because it\'s been replaced by the method "isDisplayed()" of the same class.',
            __METHOD__,
        );

        return $this->isDisplayed($entityDto);
    }

    public function isDisplayed(?EntityDto $entityDto = null): bool
    {
        return null === $this->displayCallable || (bool) \call_user_func($this->displayCallable, $entityDto?->getInstance());
    }

    public function setDisplayCallable(callable $displayCallable): void
    {
        $this->displayCallable = $displayCallable;
    }

    public function getVariant(): ButtonVariant
    {
        return $this->variant;
    }

    public function setVariant(?ButtonVariant $variant): void
    {
        $this->variant = $variant;
    }

    public function getStyle(): ButtonStyle
    {
        return $this->style;
    }

    public function setStyle(?ButtonStyle $style): void
    {
        $this->style = $style;
    }

    public function usesTextStyle(): bool
    {
        return ButtonStyle::Text === $this->style;
    }

    /**
     * @internal
     */
    public function getAsConfigObject(): Action
    {
        $action = Action::new($this->name, $this->label, $this->icon);
        $action->setCssClass($this->cssClass);
        $action->addCssClass($this->addedCssClass);
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

        if ('a' === $this->htmlElement->value) {
            $action->renderAsLink();
        } elseif ('form' === $this->htmlElement->value) {
            $action->renderAsForm();
        } else {
            $action->renderAsButton($this->buttonType);
        }

        $action->asTextLink(ButtonStyle::Text === $this->style);

        $action->asPrimaryAction(ButtonVariant::Primary === $this->variant);
        $action->asDefaultAction(ButtonVariant::Default === $this->variant);
        $action->asSuccessAction(ButtonVariant::Success === $this->variant);
        $action->asDangerAction(ButtonVariant::Danger === $this->variant);
        $action->asWarningAction(ButtonVariant::Warning === $this->variant);

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
