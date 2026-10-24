<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\IconSet;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\IconDto;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;

/**
 * Renders an icon of any icon set (FontAwesome, internal SVG icons, Symfony UX Icons).
 *
 * Unlike other components, the base size of icons is not 'md' but the size of the
 * surrounding text, so no size means no CSS class and every 'size' value (including
 * 'md') emits its own class mapped to the '--icon-size-*' design tokens.
 */
class Icon
{
    private const FILETYPES_ICON_PREFIX = 'filetypes';
    private const ICONS_DIR = __DIR__.'/../../../assets/icons';

    /**
     * The name of the icon (e.g. 'fa-user' for FontAwesome icons or 'internal:delete' for EasyAdmin icons).
     */
    public ?string $name = null;
    /**
     * The size of the icon: 'sm', 'md' or 'lg'; when null, the icon follows the size of the surrounding text.
     */
    public ?Size $size = null;
    private ?string $iconSet = null;

    /** @var array<string, array{path: string, contents: string}> */
    private static array $internalIconCache = [];

    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
    ) {
    }

    public function mount(string|Size|null $size = null): void
    {
        // unknown sizes fall back to no size, so the icon follows the size of the text
        $this->size = \is_string($size) ? Size::tryFrom($size) : $size;
    }

    public function getDefaultCssClass(): string
    {
        if (null === $this->size) {
            return 'icon';
        }

        return 'icon ea-icon-'.$this->size->value;
    }

    public function isBuiltInIconSet(): bool
    {
        return IconSet::Custom !== $this->getIconSet();
    }

    public function isFontAwesomeIconSet(): bool
    {
        return IconSet::FontAwesome === $this->getIconSet();
    }

    public function getIcon(): IconDto
    {
        return $this->getIconDto(trim($this->name ?? ''), $this->getIconSet());
    }

    private function getIconSet(): string
    {
        return $this->iconSet ?? ($this->iconSet = $this->adminContextProvider->getContext()?->getAssets()->getIconSet() ?? IconSet::FontAwesome);
    }

    private function getDefaultIconPrefix(): string
    {
        return $this->adminContextProvider->getContext()?->getAssets()->getDefaultIconPrefix() ?? '';
    }

    private function getIconDto(string $iconName, string $iconSet): IconDto
    {
        if (str_starts_with($iconName, IconSet::Internal.':') || str_starts_with($iconName, self::FILETYPES_ICON_PREFIX.':')) {
            return $this->getInternalIcon($iconName);
        }

        if ('' !== $defaultIconPrefix = $this->getDefaultIconPrefix()) {
            $iconName = sprintf('%s:%s', $defaultIconPrefix, $iconName);
        }

        return IconDto::new(name: $iconName, iconSet: $iconSet);
    }

    private function getInternalIcon(string $internalIconName): IconDto
    {
        // the same internal icons are rendered many times per page (e.g. once per
        // row on CRUD index pages), so cache the SVG file contents to avoid
        // reading the same files from disk repeatedly
        if (null === $cachedIcon = self::$internalIconCache[$internalIconName] ?? null) {
            [$iconPrefix, $iconName] = explode(':', $internalIconName, 2);
            if (1 !== preg_match('/^[a-zA-Z0-9_-]+$/D', $iconName)) {
                throw new \RuntimeException(sprintf('The icon "%s" does not exist. Check the icon name spelling and make sure that the "%s.svg" file exists in the "assets/icons/%s/" directory of EasyAdmin.', $internalIconName, $iconName, $iconPrefix));
            }

            $iconFilePath = sprintf('%s/%s/%s.svg', self::ICONS_DIR, $iconPrefix, $iconName);
            $content = @file_get_contents($iconFilePath);
            if (!\is_string($content)) {
                throw new \RuntimeException(sprintf('The icon "%s" does not exist. Check the icon name spelling and make sure that the "%s.svg" file exists in the "assets/icons/%s/" directory of EasyAdmin.', $internalIconName, $iconName, $iconPrefix));
            }

            $cachedIcon = self::$internalIconCache[$internalIconName] = ['path' => $iconFilePath, 'contents' => $content];
        }

        return IconDto::new(name: $internalIconName, path: $cachedIcon['path'], svgContents: $cachedIcon['contents'], iconSet: IconSet::Internal);
    }
}
