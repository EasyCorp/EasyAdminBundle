<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
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
    private ButtonVariant $variant = ButtonVariant::Default;
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
            fn ($item) => $item instanceof ActionDto
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
            fn ($item) => !($item instanceof ActionDto && $item->getName() === $actionName)
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

    public function getVariant(): ButtonVariant
    {
        return $this->variant;
    }

    public function setVariant(?ButtonVariant $variant): void
    {
        $this->variant = $variant ?? ButtonVariant::Default;
    }

    public function getStyle(): ButtonStyle
    {
        return $this->style;
    }

    public function hasAnyActionWithIcon(): bool
    {
        return $this->hasAnyActionWithIcon;
    }

    public function setHasAnyActionWithIcon(bool $hasAnyActionWithIcon): void
    {
        $this->hasAnyActionWithIcon = $hasAnyActionWithIcon;
    }
}
