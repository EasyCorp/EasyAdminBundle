<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class MenuItemDto
{
    public const TYPE_CRUD = 'crud';
    public const TYPE_URL = 'url';
    public const TYPE_MODAL = 'modal';
    public const TYPE_SECTION = 'section';
    public const TYPE_EXIT_IMPERSONATION = 'exit_impersonation';
    public const TYPE_DASHBOARD = 'dashboard';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_SUBMENU = 'submenu';
    public const TYPE_ROUTE = 'route';

    private ?string $type = null;
    private ?int $index = null;
    private ?int $subIndex = null;
    private ?string $label = null;
    private ?string $icon = null;
    private string $cssClass = '';
    private ?string $permission = null;
    private ?string $routeName = null;
    private ?array $routeParameters = null;
    private ?string $linkUrl = null;
    private ?string $linkModal = null;
    private string $templatePathModal = '';
    private string $linkRel = '';
    private string $linkTarget = '_self';
    private array $translationParameters = [];
    private ?MenuItemBadgeDto $badge = null;
    /** @var MenuItemDto[] */
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

    public function getLinkModal(): ?string
    {
        return $this->linkModal;
    }

    public function setLinkModal(?string $linkModal): void
    {
        $this->linkModal = $linkModal;
    }

    public function getTemplatePathModal(): ?string
    {
        return $this->templatePathModal;
    }

    public function setTemplatePathModal(?string $templatePathModal): void
    {
        $this->templatePathModal = $templatePathModal;
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

    public function getBadge(): ?MenuItemBadgeDto
    {
        return $this->badge;
    }

    public function setBadge(mixed $content, string $style): void
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
