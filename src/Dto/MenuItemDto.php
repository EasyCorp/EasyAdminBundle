<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Builder\MenuItemBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem;

final class MenuItemDto
{
    private $type;
    private $index;
    private $subIndex;
    private $label;
    private $icon;
    private $cssClass;
    private $permission;
    private $routeName;
    private $routeParameters;
    private $linkUrl;
    private $linkRel;
    private $linkTarget;
    private $translationDomain;
    private $translationParameters;
    /** @var MenuItem[]|MenuItemDto[] */
    private $subItems;

    public function __construct(string $type, string $label, ?string $icon, ?string $permission, ?string $cssClass, ?string $routeName, ?array $routeParameters, ?string $linkUrl, string $linkRel, string $linkTarget, ?string $translationDomain, array $translationParameters, array $subItems)
    {
        $this->type = $type;
        $this->label = $label;
        $this->icon = $icon;
        $this->permission = $permission;
        $this->cssClass = $cssClass;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
        $this->linkUrl = $linkUrl;
        $this->linkRel = $linkRel;
        $this->linkTarget = $linkTarget;
        $this->translationDomain = $translationDomain;
        $this->translationParameters = $translationParameters;
        $this->subItems = $subItems;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getSubIndex(): int
    {
        return $this->subIndex;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getLinkUrl(): string
    {
        return $this->linkUrl;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getRouteParameters(): ?array
    {
        return $this->routeParameters;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function getLinkRel(): string
    {
        return $this->linkRel;
    }

    public function getLinkTarget(): string
    {
        return $this->linkTarget;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    public function getSubItems(): array
    {
        return $this->subItems;
    }

    public function isSelected(?int $selectedIndex, ?int $selectedSubIndex = null): bool
    {
        if (null === $selectedSubIndex) {
            return $this->getIndex() === $selectedIndex;
        }

        return $this->getIndex() === $selectedIndex && $this->getSubIndex() === $selectedSubIndex;
    }

    public function isExpanded(?int $selectedIndex, ?int $selectedSubIndex): bool
    {
        return $this->isSelected($selectedIndex) && -1 !== $selectedSubIndex;
    }

    public function hasSubItems(): bool
    {
        return MenuItemBuilder::TYPE_SUBMENU === $this->type && count($this->subItems) > 0;
    }

    public function isMenuSection(): bool
    {
        return MenuItemBuilder::TYPE_SECTION === $this->type;
    }

    public function withProperties(array $properties): self
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if (!property_exists($this, $propertyName)) {
                throw new \InvalidArgumentException(sprintf('The "%s" option is not a valid action context option name. Valid option names are: %s', $propertyName, implode(', ', array_keys(get_object_vars($this)))));
            }

            $this->{$propertyName} = $propertyValue;
        }

        return $this;
    }
}
