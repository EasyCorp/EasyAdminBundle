<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class WidgetDto
{
    private ?string $widgetFqcn = null;
    private ?string $name = null;
    private TranslatableInterface|string|null $label = null;
    private ?string $icon = null;
    private TranslatableInterface|string|null $help = null;
    private ?string $linkUrl = null;
    private TranslatableInterface|string|null $linkLabel = null;
    private string|Expression|null $permission = null;
    private ?int $columns = null;
    private int $defaultColumns = 12;
    private ?int $rows = null;
    private bool $lazy = false;
    private ?int $cacheTtl = null;
    private bool $cacheShared = false;
    private ?int $refreshInterval = null;
    private string $cssClass = '';
    private ?string $templateName = null;
    private ?string $templatePath = null;
    private AssetsDto $assets;
    private WidgetPlacement $placement = WidgetPlacement::Grid;
    private bool $renderedWithoutCard = false;
    private KeyValueStore $customOptions;
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct()
    {
        $this->assets = new AssetsDto();
        $this->customOptions = KeyValueStore::new();
    }

    public function __clone()
    {
        $this->assets = clone $this->assets;
        $this->customOptions = clone $this->customOptions;
    }

    public function getWidgetFqcn(): ?string
    {
        return $this->widgetFqcn;
    }

    public function setWidgetFqcn(string $widgetFqcn): void
    {
        $this->widgetFqcn = $widgetFqcn;
    }

    /**
     * The kind of widget in kebab-case, derived from the class name
     * (e.g. 'stat' for StatWidget, 'latest-orders' for LatestOrdersWidget).
     * It's used in the CSS class of the widget ('ea-widget-stat').
     */
    public function getType(): string
    {
        // the name of anonymous classes includes the path of the file where they are defined
        if (null === $this->widgetFqcn || str_contains($this->widgetFqcn, '@anonymous')) {
            return 'custom';
        }

        $shortName = substr($this->widgetFqcn, (int) strrpos('\\'.$this->widgetFqcn, '\\'));
        $shortName = preg_replace('/Widget$/', '', $shortName);

        $type = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', (string) preg_replace('/[^A-Za-z0-9]/', '', $shortName)));

        return '' === $type ? 'custom' : $type;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): TranslatableInterface|string|null
    {
        return $this->label;
    }

    public function setLabel(TranslatableInterface|string|null $label): void
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

    public function getHelp(): TranslatableInterface|string|null
    {
        return $this->help;
    }

    public function setHelp(TranslatableInterface|string|null $help): void
    {
        $this->help = $help;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(?string $linkUrl): void
    {
        $this->linkUrl = $linkUrl;
    }

    public function getLinkLabel(): TranslatableInterface|string|null
    {
        return $this->linkLabel;
    }

    public function setLinkLabel(TranslatableInterface|string|null $linkLabel): void
    {
        $this->linkLabel = $linkLabel;
    }

    public function getPermission(): string|Expression|null
    {
        return $this->permission;
    }

    public function setPermission(string|Expression|null $permission): void
    {
        $this->permission = $permission;
    }

    /**
     * The number of columns (1 to 12) that the widget takes in the grid: the one
     * configured explicitly or, otherwise, the default one of this kind of widget.
     */
    public function getColumns(): int
    {
        return $this->columns ?? $this->defaultColumns;
    }

    public function setColumns(?int $columns): void
    {
        $this->columns = $columns;
    }

    public function getDefaultColumns(): int
    {
        return $this->defaultColumns;
    }

    public function setDefaultColumns(int $defaultColumns): void
    {
        $this->defaultColumns = $defaultColumns;
    }

    /**
     * The number of grid rows (1 to 6) that the widget takes; null means
     * that the height of the widget is the height of its contents.
     */
    public function getRows(): ?int
    {
        return $this->rows;
    }

    public function setRows(?int $rows): void
    {
        $this->rows = $rows;
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }

    public function setLazy(bool $lazy): void
    {
        $this->lazy = $lazy;
    }

    /**
     * The number of seconds that the rendered contents are cached; null means no cache.
     */
    public function getCacheTtl(): ?int
    {
        return $this->cacheTtl;
    }

    public function setCacheTtl(?int $cacheTtl): void
    {
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * Whether all users share the same cached contents (by default, each user has their own).
     */
    public function isCacheShared(): bool
    {
        return $this->cacheShared;
    }

    public function setCacheShared(bool $cacheShared): void
    {
        $this->cacheShared = $cacheShared;
    }

    /**
     * The number of seconds between each reload of the contents; null means no reload.
     */
    public function getRefreshInterval(): ?int
    {
        return $this->refreshInterval;
    }

    public function setRefreshInterval(?int $refreshInterval): void
    {
        $this->refreshInterval = $refreshInterval;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass): void
    {
        $this->cssClass = trim($cssClass);
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function setTemplateName(?string $templateName): void
    {
        $this->templateName = $templateName;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function setTemplatePath(?string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    public function getAssets(): AssetsDto
    {
        return $this->assets;
    }

    public function addWebpackEncoreAsset(AssetDto $assetDto): void
    {
        $this->assets->addWebpackEncoreAsset($assetDto);
    }

    public function addRepriseAsset(AssetDto $assetDto): void
    {
        $this->assets->addRepriseAsset($assetDto);
    }

    public function addAssetMapperAsset(AssetDto $assetDto): void
    {
        $this->assets->addAssetMapperAsset($assetDto);
    }

    public function addCssAsset(AssetDto $assetDto): void
    {
        $this->assets->addCssAsset($assetDto);
    }

    public function addJsAsset(AssetDto $assetDto): void
    {
        $this->assets->addJsAsset($assetDto);
    }

    public function addHtmlContentToHead(string $htmlContent): void
    {
        $this->assets->addHtmlContentToHead($htmlContent);
    }

    public function addHtmlContentToBody(string $htmlContent): void
    {
        $this->assets->addHtmlContentToBody($htmlContent);
    }

    public function getPlacement(): WidgetPlacement
    {
        return $this->placement;
    }

    public function setPlacement(WidgetPlacement $placement): void
    {
        $this->placement = $placement;
    }

    public function isRenderedWithoutCard(): bool
    {
        return $this->renderedWithoutCard;
    }

    public function setRenderedWithoutCard(bool $renderedWithoutCard): void
    {
        $this->renderedWithoutCard = $renderedWithoutCard;
    }

    public function getCustomOptions(): KeyValueStore
    {
        return $this->customOptions;
    }

    public function getCustomOption(string $optionName): mixed
    {
        return $this->customOptions->get($optionName);
    }

    /**
     * @param array<string, mixed> $customOptions
     */
    public function setCustomOptions(array $customOptions): void
    {
        $this->customOptions = KeyValueStore::new($customOptions);
    }

    public function setCustomOption(string $optionName, mixed $optionValue): void
    {
        $this->customOptions->set($optionName, $optionValue);
    }

    /**
     * The data is what the widget displays (the value of a stat, the items of
     * a list, etc.). Each entry is either the value itself or a closure (or
     * invokable object) that returns it. Closures can receive a WidgetContext and
     * they are only called when the widget is rendered: never for widgets that
     * the user can't see and, for lazy widgets, only in their own request.
     *
     * The resolved data is passed to the widget template in the 'data' variable.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setData(string $name, mixed $valueOrCallable): void
    {
        $this->data[$name] = $valueOrCallable;
    }
}
