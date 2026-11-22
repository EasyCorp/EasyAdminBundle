<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Provides the options shared by all widgets. Custom widgets use this trait,
 * implement WidgetInterface and define their own named constructor:
 *
 *     public static function new(TranslatableInterface|string|null $label = null): self
 *     {
 *         return (new self())
 *             ->setLabel($label)
 *             ->setTemplatePath('admin/widget/my_widget.html.twig')
 *             ->setDefaultColumns(4);
 *     }
 *
 *     public function setProduct(Product $product): self
 *     {
 *         // the template gets it in the 'data.sales' variable
 *         return $this->setDataEntry('sales', fn (): int => $product->countSales());
 *     }
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait WidgetTrait
{
    private WidgetDto $dto;

    private function __construct()
    {
        $this->dto = new WidgetDto();
        $this->dto->setWidgetFqcn(self::class);
    }

    public function __clone(): void
    {
        $this->dto = clone $this->dto;
    }

    /**
     * The name identifies the widget in templates (<twig:ea:Widget name="..." />),
     * in the URLs used to load it and in its cache keys. If not set, it's
     * the slug of the label (e.g. 'latest-orders' for 'Latest orders').
     */
    public function setName(string $name): self
    {
        if (1 !== preg_match('/^[a-zA-Z0-9_-]+$/D', $name)) {
            throw new \InvalidArgumentException(sprintf('The name of a widget can only contain letters, numbers, dashes and underscores ("%s" given).', $name));
        }

        $this->dto->setName($name);

        return $this;
    }

    public function setLabel(TranslatableInterface|string|null $label): self
    {
        $this->dto->setLabel($label);

        return $this;
    }

    /**
     * @param string|null $icon The name of the icon displayed before the label (e.g. 'fa fa-users')
     */
    public function setIcon(?string $icon): self
    {
        $this->dto->setIcon($icon);

        return $this;
    }

    public function setHelp(TranslatableInterface|string $help): self
    {
        $this->dto->setHelp($help);

        return $this;
    }

    /**
     * Displays a link in the header of the widget (e.g. to the full list of the items it shows).
     *
     * @param TranslatableInterface|string|null $label The text of the link; if null, the link only displays an icon
     */
    public function setLink(string $url, TranslatableInterface|string|null $label = null): self
    {
        if ('' === trim($url)) {
            throw new \InvalidArgumentException('The URL of the widget link cannot be empty.');
        }

        $this->dto->setLinkUrl($url);
        $this->dto->setLinkLabel($label);

        return $this;
    }

    /**
     * Widgets are hidden, and their data is not computed, for users without this permission.
     */
    public function setPermission(string|Expression $permission): self
    {
        $this->dto->setPermission($permission);

        return $this;
    }

    /**
     * @param int $columns The number of columns (1 to 12) that the widget takes in the 12-column grid of the dashboard
     */
    public function setColumns(int $columns): self
    {
        $this->dto->setColumns(self::validateColumns($columns));

        return $this;
    }

    /**
     * Used to define the columns of widgets when users don't define them
     * explicitly using the setColumns() method.
     * This should only be used if you create a custom EasyAdmin widget,
     * not when configuring widgets in your dashboard.
     */
    public function setDefaultColumns(int $columns): self
    {
        $this->dto->setDefaultColumns(self::validateColumns($columns));

        return $this;
    }

    /**
     * @param int $rows The number of grid rows (1 to 6) that the widget takes; by default, the height of widgets is the height of their contents
     */
    public function setRows(int $rows): self
    {
        if ($rows < 1 || $rows > 6) {
            throw new \InvalidArgumentException(sprintf('The number of rows of a widget must be between 1 and 6 (%d given).', $rows));
        }

        $this->dto->setRows($rows);

        return $this;
    }

    /**
     * Lazy widgets display a placeholder and load their contents in a separate request.
     * Use it for widgets that are slow to compute, so they don't delay the rest of the page.
     */
    public function setLazy(bool $lazy = true): self
    {
        $this->dto->setLazy($lazy);

        return $this;
    }

    /**
     * Caches the rendered contents of the widget.
     *
     * @param int  $seconds The time that the contents are cached
     * @param bool $shared  By default, each user has their own cached contents; set it to true when
     *                      all users see the same contents, so they are computed once for all of them
     */
    public function setCache(int $seconds, bool $shared = false): self
    {
        if ($seconds < 1) {
            throw new \InvalidArgumentException(sprintf('The number of seconds that a widget is cached must be 1 or more (%d given).', $seconds));
        }

        $this->dto->setCacheTtl($seconds);
        $this->dto->setCacheShared($shared);

        return $this;
    }

    /**
     * Reloads the contents of the widget periodically while the page is visible.
     *
     * @param int $seconds The time between reloads (between 10 seconds and one day)
     */
    public function setRefreshInterval(int $seconds): self
    {
        if ($seconds < 10 || $seconds > 86400) {
            throw new \InvalidArgumentException(sprintf('The refresh interval of a widget must be between 10 and 86400 seconds (%d given).', $seconds));
        }

        $this->dto->setRefreshInterval($seconds);

        return $this;
    }

    public function addCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($this->dto->getCssClass().' '.$cssClass);

        return $this;
    }

    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($cssClass);

        return $this;
    }

    /**
     * @param string $name The name of one of the templates defined by EasyAdmin (e.g. 'widget/stat')
     */
    public function setTemplateName(string $name): self
    {
        $this->dto->setTemplateName($name);
        $this->dto->setTemplatePath(null);

        return $this;
    }

    /**
     * @param string $path The path of the Twig template that renders the contents of the widget
     */
    public function setTemplatePath(string $path): self
    {
        $this->dto->setTemplateName(null);
        $this->dto->setTemplatePath($path);

        return $this;
    }

    public function addWebpackEncoreEntries(Asset|string ...$entryNamesOrAssets): self
    {
        if (!class_exists('Symfony\\WebpackEncoreBundle\\Twig\\EntryFilesTwigExtension')) {
            throw new \RuntimeException('You are trying to add Webpack Encore entries in a widget but Webpack Encore is not installed in your project. Try running "composer require symfony/webpack-encore-bundle"');
        }

        foreach ($entryNamesOrAssets as $entryNameOrAsset) {
            $this->dto->addWebpackEncoreAsset(\is_string($entryNameOrAsset) ? new AssetDto($entryNameOrAsset) : $entryNameOrAsset->getAsDto());
        }

        return $this;
    }

    public function addRepriseEntries(Asset|string ...$entryNamesOrAssets): self
    {
        if (!class_exists('Symfony\\Reprise\\Twig\\AssetExtension')) {
            throw new \RuntimeException('You are trying to add Reprise entries in a widget but Symfony Reprise is not installed in your project. Try running "composer require symfony/reprise"');
        }

        foreach ($entryNamesOrAssets as $entryNameOrAsset) {
            $this->dto->addRepriseAsset(\is_string($entryNameOrAsset) ? new AssetDto($entryNameOrAsset) : $entryNameOrAsset->getAsDto());
        }

        return $this;
    }

    public function addAssetMapperEntries(Asset|string ...$entryNamesOrAssets): self
    {
        if (!class_exists('Symfony\\Component\\AssetMapper\\AssetMapper')) {
            throw new \RuntimeException('You are trying to add AssetMapper entries in a widget but AssetMapper is not installed in your project. Try running "composer require symfony/asset-mapper"');
        }

        foreach ($entryNamesOrAssets as $entryNameOrAsset) {
            $this->dto->addAssetMapperAsset(\is_string($entryNameOrAsset) ? new AssetDto($entryNameOrAsset) : $entryNameOrAsset->getAsDto());
        }

        return $this;
    }

    public function addCssFiles(Asset|string ...$pathsOrAssets): self
    {
        foreach ($pathsOrAssets as $pathOrAsset) {
            $this->dto->addCssAsset(\is_string($pathOrAsset) ? new AssetDto($pathOrAsset) : $pathOrAsset->getAsDto());
        }

        return $this;
    }

    public function addJsFiles(Asset|string ...$pathsOrAssets): self
    {
        foreach ($pathsOrAssets as $pathOrAsset) {
            $this->dto->addJsAsset(\is_string($pathOrAsset) ? new AssetDto($pathOrAsset) : $pathOrAsset->getAsDto());
        }

        return $this;
    }

    public function addHtmlContentsToHead(string ...$contents): self
    {
        foreach ($contents as $content) {
            $this->dto->addHtmlContentToHead($content);
        }

        return $this;
    }

    public function addHtmlContentsToBody(string ...$contents): self
    {
        foreach ($contents as $content) {
            $this->dto->addHtmlContentToBody($content);
        }

        return $this;
    }

    public function setPlacement(WidgetPlacement $placement): self
    {
        $this->dto->setPlacement($placement);

        return $this;
    }

    /**
     * Renders only the contents of the widget, without the card that surrounds
     * them and without its header (label, icon, help and link).
     */
    public function renderWithoutCard(bool $withoutCard = true): self
    {
        $this->dto->setRenderedWithoutCard($withoutCard);

        return $this;
    }

    public function setCustomOption(string $optionName, mixed $optionValue): self
    {
        $this->dto->setCustomOption($optionName, $optionValue);

        return $this;
    }

    /**
     * Sets one of the values that the widget displays, which its template gets
     * in the 'data' variable (e.g. 'data.value'). It's either the value itself
     * or a closure (or invokable object) that returns it; closures can receive
     * a WidgetContext and they are only called when the widget is rendered.
     */
    protected function setDataEntry(string $name, mixed $valueOrCallable): self
    {
        $this->dto->setData($name, $valueOrCallable);

        return $this;
    }

    public function getAsDto(): WidgetDto
    {
        return $this->dto;
    }

    private static function validateColumns(int $columns): int
    {
        if ($columns < 1 || $columns > 12) {
            throw new \InvalidArgumentException(sprintf('The number of columns of a widget must be between 1 and 12 (%d given).', $columns));
        }

        return $columns;
    }
}
