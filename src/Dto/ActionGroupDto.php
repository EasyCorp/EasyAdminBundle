<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonColor;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonStyle;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonVariant;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionGroupDto
{
    private ?string $type = null;
    private ?string $name = null;
    private TranslatableInterface|string|false|null $label = null;
    private ?string $icon = null;
    private string $cssClass = '';
    private string $addedCssClass = '';
    /** @var array<string, string> */
    private array $htmlAttributes = [];
    private ?string $templatePath = null;
    private ?ActionDto $mainAction = null;
    /** @var array<int, ActionDto|array{type: string, content?: mixed}> */
    private array $items = [];
    /** @var callable|null */
    private $displayCallable;
    private ButtonColor $color = ButtonColor::Default;
    private ?ButtonVariant $variant = null;
    private ButtonStyle $style = ButtonStyle::Solid;
    private bool $hasAnyActionWithIcon = false;

    public function __clone(): void
    {
        if (null !== $this->mainAction) {
            $this->mainAction = clone $this->mainAction;
        }

        foreach ($this->items as $index => $item) {
            if ($item instanceof ActionDto) {
                $this->items[$index] = clone $item;
            }
        }
    }

    // A utility method for Twig templates that deal with actions and action groups
    public function isActionGroup(): bool
    {
        return true;
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
        return ActionGroup::TYPE_ENTITY === $this->type;
    }

    public function isGlobalAction(): bool
    {
        return ActionGroup::TYPE_GLOBAL === $this->type;
    }

    public function isBatchAction(): bool
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

    public function getMainAction(): ?ActionDto
    {
        return $this->mainAction;
    }

    public function setMainAction(?ActionDto $mainAction): void
    {
        $this->mainAction = $mainAction;
    }

    public function hasMainAction(): bool
    {
        return null !== $this->mainAction;
    }

    /**
     * Returns only the ActionDto items (excluding dividers and headers).
     *
     * @return ActionDto[]
     */
    public function getActions(): array
    {
        return array_values(array_filter(
            $this->items,
            static fn ($item) => $item instanceof ActionDto
        ));
    }

    /**
     * Returns all items including actions, dividers, and headers.
     *
     * @return array<int, ActionDto|array{type: string, content?: mixed}>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param list<ActionDto|mixed> $actions
     */
    public function setActions(array $actions): void
    {
        $this->items = [];
        foreach ($actions as $action) {
            if ($action instanceof ActionDto) {
                $this->items[] = $action;
            }
        }
    }

    public function addAction(ActionDto $action): void
    {
        $this->items[] = $action;
    }

    public function removeAction(string $actionName): void
    {
        $this->items = array_filter(
            $this->items,
            static fn ($item) => !($item instanceof ActionDto && $item->getName() === $actionName)
        );
        $this->items = array_values($this->items);
    }

    public function addDivider(): void
    {
        $this->items[] = ['type' => 'divider'];
    }

    public function addHeader(TranslatableInterface|string $label): void
    {
        $this->items[] = ['type' => 'header', 'content' => $label];
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

    public function hasAnyActionWithIcon(): bool
    {
        return $this->hasAnyActionWithIcon;
    }

    public function setHasAnyActionWithIcon(bool $hasAnyActionWithIcon): void
    {
        $this->hasAnyActionWithIcon = $hasAnyActionWithIcon;
    }

    public function getAsConfigObject(): ActionGroup
    {
        $action = ActionGroup::new($this->name, $this->label, $this->icon);
        $action->setCssClass($this->cssClass);
        $action->addCssClass($this->addedCssClass);
        $action->setHtmlAttributes($this->htmlAttributes);
        $action->setHtmlAttributes($this->htmlAttributes);

        if (null !== $this->templatePath) {
            $action->setTemplatePath($this->templatePath);
        }

        if ($this->isGlobalAction()) {
            $action->createAsGlobalActionGroup();
        }

        if (null !== $this->mainAction) {
            $action->addMainAction($this->mainAction->getAsConfigObject());
        }

        foreach ($this->items as $index => $item) {
            if ($item instanceof ActionDto) {
                $action->addAction($item->getAsConfigObject());
            } elseif ('divider' === ($item['type'] ?? null)) {
                $action->addDivider();
            } elseif ('header' === ($item['type'] ?? null)) {
                $action->addHeader($item['content']);
            }
        }

        if (null !== $this->displayCallable) {
            $action->displayIf($this->displayCallable);
        }

        return $action;
    }
}
