<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonColor;
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
    /** @var TranslatableInterface|string|callable|false|null */
    private mixed $label = null;
    private ?string $icon = null;
    private string $cssClass = '';
    private string $addedCssClass = '';
    /** @var array<string, string|TranslatableInterface> */
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
    private ButtonColor $color = ButtonColor::Default;
    private ?ButtonVariant $variant = null;
    private ButtonStyle $style = ButtonStyle::Solid;
    private bool|string|TranslatableInterface $confirmationMessage = false;
    private string|TranslatableInterface|null $displayableConfirmationMessage = null;
    private string|TranslatableInterface|null $confirmationButtonLabel = null;

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
     * @return array<string, string|TranslatableInterface>
     */
    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    /**
     * @param array<string, string|TranslatableInterface> $htmlAttributes
     */
    public function addHtmlAttributes(array $htmlAttributes): void
    {
        $this->htmlAttributes = array_merge($this->htmlAttributes, $htmlAttributes);
    }

    /**
     * @param array<string, string|TranslatableInterface> $htmlAttributes
     */
    public function setHtmlAttributes(array $htmlAttributes): void
    {
        $this->htmlAttributes = $htmlAttributes;
    }

    public function setHtmlAttribute(string $attributeName, string|TranslatableInterface $attributeValue): void
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
    public function getRouteParameters(): array|callable
    {
        return $this->routeParameters;
    }

    /**
     * @param array<string, mixed>|callable $routeParameters
     */
    public function setRouteParameters(array|callable $routeParameters): void
    {
        $this->routeParameters = $routeParameters;
    }

    public function getUrl(): callable|string|null
    {
        return $this->url;
    }

    public function setUrl(string|callable $url): void
    {
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

    public function isDisplayed(?EntityDto $entityDto = null): bool
    {
        return null === $this->displayCallable || (bool) \call_user_func($this->displayCallable, $entityDto?->getInstance());
    }

    public function setDisplayCallable(callable $displayCallable): void
    {
        $this->displayCallable = $displayCallable;
    }

    /**
     * @deprecated since EasyAdmin 5.9.0, use getColor() and getButtonVariant() instead
     */
    public function getVariant(): ButtonVariant
    {
        trigger_deprecation('easycorp/easyadmin-bundle', '5.9.0', 'The "%s()" method is deprecated, use "getColor()" and "getButtonVariant()" instead.', __METHOD__);

        return $this->variant ?? ButtonVariant::from($this->color->value);
    }

    /**
     * Sets the style of the button. Passing a color (or null) is deprecated: use setColor() instead.
     */
    public function setVariant(?ButtonVariant $variant): void
    {
        if (null !== $variant && !$variant->isColor()) {
            $this->variant = $variant;

            return;
        }

        trigger_deprecation('easycorp/easyadmin-bundle', '5.9.0', 'Passing a color or null to "%s()" is deprecated, use "setColor()" instead.', __METHOD__);

        $this->variant = null;
        $this->color = $variant?->asColor() ?? ButtonColor::Default;
    }

    public function setColor(ButtonColor $color): void
    {
        $this->color = $color;
    }

    /**
     * The color configured for this action, without considering its CSS classes.
     */
    public function getConfiguredColor(): ButtonColor
    {
        return $this->color;
    }

    public function getStyle(): ButtonStyle
    {
        return $this->style;
    }

    /**
     * The color of the button that renders this action.
     */
    public function getColor(): ButtonColor
    {
        if (ButtonColor::Default !== $this->color) {
            return $this->color;
        }

        // actions styled with CSS classes (e.g. addCssClass('btn btn-primary')) instead of the
        // asXxxAction() methods keep the color of those classes, as in previous versions
        $cssClasses = $this->getCssClass().' '.$this->getAddedCssClass();
        if (1 === preg_match('/(?:^|\s)btn-(?:outline-)?(primary|success|warning|danger|info)(?:\s|$)/', $cssClasses, $matches)) {
            return ButtonColor::from($matches[1]);
        }

        return ButtonColor::Default;
    }

    /**
     * The style of the button that renders this action: text actions are ghost buttons
     * and the others use the default style of their color (e.g. primary actions are
     * solid buttons and default actions are outline buttons).
     */
    public function getButtonVariant(): ButtonVariant
    {
        if (null !== $this->variant) {
            return $this->variant;
        }

        return ButtonStyle::Text === $this->style ? ButtonVariant::Ghost : $this->getColor()->getDefaultVariant();
    }

    public function setStyle(?ButtonStyle $style): void
    {
        $this->style = $style;
    }

    public function usesTextStyle(): bool
    {
        return ButtonStyle::Text === $this->style;
    }

    public function getConfirmationMessage(): bool|string|TranslatableInterface
    {
        return $this->confirmationMessage;
    }

    public function setConfirmationMessage(bool|string|TranslatableInterface $message): void
    {
        $this->confirmationMessage = $message;
    }

    public function hasConfirmation(): bool
    {
        return false !== $this->confirmationMessage;
    }

    public function getDisplayableConfirmationMessage(): string|TranslatableInterface|null
    {
        return $this->displayableConfirmationMessage;
    }

    public function setDisplayableConfirmationMessage(string|TranslatableInterface|null $message): void
    {
        $this->displayableConfirmationMessage = $message;
    }

    public function getConfirmationButtonLabel(): string|TranslatableInterface|null
    {
        return $this->confirmationButtonLabel;
    }

    public function setConfirmationButtonLabel(string|TranslatableInterface|null $label): void
    {
        $this->confirmationButtonLabel = $label;
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

        $action->asPrimaryAction(ButtonColor::Primary === $this->color);
        $action->asDefaultAction(ButtonColor::Default === $this->color);
        $action->asSuccessAction(ButtonColor::Success === $this->color);
        $action->asDangerAction(ButtonColor::Danger === $this->color);
        $action->asWarningAction(ButtonColor::Warning === $this->color);
        $action->asInfoAction(ButtonColor::Info === $this->color);

        if (null !== $this->crudActionName) {
            $action->linkToCrudAction($this->crudActionName);
        }

        if (null !== $this->routeName) {
            $action->linkToRoute($this->routeName, $this->routeParameters);
        }

        if (null !== $this->url) {
            $action->linkToUrl($this->url);
        }

        if (null !== $this->displayCallable) {
            $action->displayIf($this->displayCallable);
        }

        if (false !== $this->confirmationMessage) {
            $action->askConfirmation($this->confirmationMessage, $this->confirmationButtonLabel);
        }

        return $action;
    }
}
