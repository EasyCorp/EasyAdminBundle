<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class MenuItemDto
{
    public const TYPE_CRUD = 'crud';
    public const TYPE_URL = 'url';
    public const TYPE_SECTION = 'section';
    public const TYPE_EXIT_IMPERSONATION = 'exit_impersonation';
    public const TYPE_DASHBOARD = 'dashboard';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_SUBMENU = 'submenu';
    public const TYPE_ROUTE = 'route';

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
    private $translationParameters;
    /** @var MenuItemBadgeDto|null */
    private $badge;
    /** @var MenuItemDto[] */
    private $subItems;

    public function __construct()
    {
        $this->cssClass = '';
        $this->translationParameters = [];
        $this->linkRel = '';
        $this->linkTarget = '_self';
        $this->badge = null;
        $this->subItems = [];
    }

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
        return $this->index;
    }

    public function setIndex(int $index): void
    {
        $this->index = $index;
    }

    public function getSubIndex(): int
    {
        return $this->subIndex;
    }

    public function setSubIndex(int $subIndex): void
    {
        $this->subIndex = $subIndex;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
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

    public function setRouteParameter(string $parameterName, $parameterValue): void
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

    public function getBadge(): ?MenuItemBadgeDto
    {
        return $this->badge;
    }

    public function setBadge($content, string $style): void
    {
        $this->badge = new MenuItemBadgeDto($content, trim($style));
    }

    /**
     * @return MenuItemDto[]
     */
    public function getSubItems(): array
    {
        return $this->subItems;
    }

    /**
     * @param MenuItemDto[] $subItems
     */
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
