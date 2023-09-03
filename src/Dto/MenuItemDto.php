<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Symfony\Contracts\Translation\TranslatableInterface;

final class MenuItemDto implements MenuItemDtoInterface
{
    private ?string $type = null;
    private bool $selected = false;
    private bool $expanded = false;
    private TranslatableInterface|string|null $label = null;
    private ?string $icon = null;
    private string $cssClass = '';
    private ?string $permission = null;
    private ?string $routeName = null;
    private ?array $routeParameters = null;
    private ?string $linkUrl = null;
    private string $linkRel = '';
    private string $linkTarget = '_self';
    private array $translationParameters = [];
    private ?MenuItemBadgeDto $badge = null;
    /** @var MenuItemDtoInterface[] */
    private array $subItems = [];

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getIndex(): int
    {
        return -1;
    }

    public function setIndex(int $index): void
    {
        // do nothing...
    }

    public function getSubIndex(): int
    {
        return -1;
    }

    public function setSubIndex(int $subIndex): void
    {
        // do nothing
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $isSelected): void
    {
        $this->selected = $isSelected;
    }

    public function isExpanded(): bool
    {
        return $this->expanded;
    }

    public function setExpanded(bool $isExpanded): void
    {
        $this->expanded = $isExpanded;
    }

    public function getLabel(): TranslatableInterface|string
    {
        return $this->label;
    }

    public function setLabel(TranslatableInterface|string $label): void
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

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(?string $linkUrl): void
    {
        $this->linkUrl = $linkUrl;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(?string $routeName): void
    {
        $this->routeName = $routeName;
    }

    public function getRouteParameters(): ?array
    {
        return $this->routeParameters;
    }

    public function setRouteParameter(string $parameterName, mixed $parameterValue): void
    {
        $this->routeParameters[$parameterName] = $parameterValue;
    }

    public function setRouteParameters(?array $routeParameters): void
    {
        $this->routeParameters = $routeParameters;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(?string $permission): void
    {
        $this->permission = $permission;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass): void
    {
        $this->cssClass = $cssClass;
    }

    public function getLinkRel(): string
    {
        return $this->linkRel;
    }

    public function setLinkRel(string $linkRel): void
    {
        $this->linkRel = $linkRel;
    }

    public function getLinkTarget(): string
    {
        return $this->linkTarget;
    }

    public function setLinkTarget(string $linkTarget): void
    {
        $this->linkTarget = $linkTarget;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    public function setTranslationParameters(array $translationParameters): void
    {
        $this->translationParameters = $translationParameters;
    }

    public function getBadge(): ?MenuItemBadgeDtoInterface
    {
        return $this->badge;
    }

    public function setBadge(mixed $content, string $style): void
    {
        $this->badge = new MenuItemBadgeDto($content, trim($style));
    }

    public function getSubItems(): array
    {
        return $this->subItems;
    }

    public function setSubItems(array $subItems): void
    {
        $this->subItems = $subItems;
    }

    public function hasSubItems(): bool
    {
        return self::TYPE_SUBMENU === $this->type && \count($this->subItems) > 0;
    }

    public function isMenuSection(): bool
    {
        return self::TYPE_SECTION === $this->type;
    }
}
