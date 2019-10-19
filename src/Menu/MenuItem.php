<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

final class MenuItem implements MenuItemInterface
{
    public const TYPE_ENTITY = 'entity';
    public const TYPE_HOMEPAGE = 'homepage';
    public const TYPE_SUBMENU = 'group';
    public const TYPE_ROUTE = 'route';
    public const TYPE_SECTION = 'section';
    public const TYPE_URL = 'url';

    private $type;
    private $index;
    private $subIndex;
    private $label;
    private $icon;
    private $url;
    private $permission;
    private $cssClass;
    private $linkRel;
    private $linkTarget;
    /** @var MenuItemInterface[] */
    private $subItems;

    /**
     * @internal Don't use this constructor; use the 'new()' named constructor
     */
    public function __construct(string $type, int $index, int $subIndex, string $label, string $icon, string $url, ?string $permission, string $cssClass, string $linkRel, string $linkTarget, array $subItems)
    {
        $this->type = $type;
        $this->index = $index;
        $this->subIndex = $subIndex;
        $this->label = $label;
        $this->icon = $icon;
        $this->url = $url;
        $this->permission = $permission;
        $this->cssClass = $cssClass;
        $this->linkRel = $linkRel;
        $this->linkTarget = $linkTarget;
        $this->subItems = $subItems;
    }

    public static function new(string $label = null, string $icon = null): MenuItemBuilder
    {
        return new MenuItemBuilder($label, $icon);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getSubindex(): int
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

    public function getUrl(): string
    {
        return $this->url;
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

    public function getSubItems(): array
    {
        return $this->subItems;
    }

    public function hasSubItems(): bool
    {
        return self::TYPE_SUBMENU === $this->type && count($this->subItems) > 0;
    }

    public function isMenuSection(): bool
    {
        return self::TYPE_SECTION === $this->type;
    }
}
