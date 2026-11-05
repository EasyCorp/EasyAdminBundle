<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
use EasyCorp\Bundle\EasyAdminBundle\Util\ThemeColorCalculator;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class ThemeDto
{
    private const GRAY_STEPS = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];
    private const CHECK_ICON_SVG = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'><path fill='none' stroke='%s' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/></svg>";
    private const RADIO_ICON_SVG = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='2' fill='%s'/></svg>";

    // the base font used when Theme::font() doesn't set one; its CSS variables are
    // the defaults of variables-theme.css, so only its preload is emitted
    private const DEFAULT_BASE_FONT = Font::Inter;

    private const BUNDLED_FONT_FILE_PREFIXES = [
        'Inter' => 'inter',
        'IbmPlexSans' => 'ibm-plex-sans',
        'Rubik' => 'rubik',
        'AtkinsonHyperlegibleNext' => 'atkinson-hyperlegible-next',
        'JetBrainsMono' => 'jetbrains-mono',
    ];

    // used by the pages rendered without a dashboard (e.g. the login page)
    public const DEFAULT_FONT_PRELOAD_PATH = 'fonts/'.self::BUNDLED_FONT_FILE_PREFIXES[self::DEFAULT_BASE_FONT->name].'-latin-wght-normal.woff2';

    // the normal, medium, semibold and bold weights of each bundled font, chosen so all
    // of them look equally bold (with the same stroke width relative to their x-height
    // as Inter); e.g. Rubik is a heavy font, so its 500 weight looks like Inter 570;
    // system fonts and Bunny Fonts use the standard weights because their files
    // usually have static weights
    private const BUNDLED_FONT_WEIGHTS = [
        'Inter' => [435, 500, 600, 700],
        'IbmPlexSans' => [435, 480, 565, 655],
        'Rubik' => [390, 425, 475, 550],
        'AtkinsonHyperlegibleNext' => [410, 450, 550, 665],
    ];
    private const STATIC_FONT_WEIGHTS = [400, 500, 600, 700];

    // Bunny Fonts serves static font files, so the stylesheet only lists the weights
    // used by the theme; bold text (700) uses the 600 file, the closest one available
    private const REMOTE_FONT_WEIGHTS = '400;500;600';

    public function __construct(
        private ?string $primaryColor = null,
        private ?string $primaryForeground = null,
        private ?string $darkPrimaryColor = null,
        private ?string $darkPrimaryForeground = null,
        private ?string $radius = null,
        private ?string $spacing = null,
        private ?string $grayRampPrefix = null,
        private ?string $darkGrayRampPrefix = null,
        private ?string $primaryOnSurface = null,
        private Font|string|null $baseFont = null,
        private Font|string|null $monospaceFont = null,
        private ?int $baseFontWeight = null,
        /** @var list<string>|null */
        private ?array $baseFontFeatures = null,
    ) {
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function getPrimaryForeground(): ?string
    {
        return $this->primaryForeground;
    }

    public function getDarkPrimaryColor(): ?string
    {
        return $this->darkPrimaryColor;
    }

    public function getDarkPrimaryForeground(): ?string
    {
        return $this->darkPrimaryForeground;
    }

    public function getPrimaryOnSurface(): ?string
    {
        return $this->primaryOnSurface;
    }

    public function getRadius(): ?string
    {
        return $this->radius;
    }

    public function getSpacing(): ?string
    {
        return $this->spacing;
    }

    public function getGrayRampPrefix(): ?string
    {
        return $this->grayRampPrefix;
    }

    public function getDarkGrayRampPrefix(): ?string
    {
        return $this->darkGrayRampPrefix;
    }

    public function getBaseFont(): Font|string|null
    {
        return $this->baseFont;
    }

    public function getMonospaceFont(): Font|string|null
    {
        return $this->monospaceFont;
    }

    public function getBaseFontWeight(): ?int
    {
        return $this->baseFontWeight;
    }

    /**
     * @return list<string>|null
     */
    public function getBaseFontFeatures(): ?array
    {
        return $this->baseFontFeatures;
    }

    /**
     * The asset paths (in the EasyAdmin asset package) of the font files that
     * the browser should start downloading as soon as possible: only the latin
     * subset of the bundled base font, which is needed by every page. The other
     * subsets and the monospace font are only downloaded when a page uses them.
     *
     * @return list<string>
     */
    public function getFontPreloadPaths(): array
    {
        $baseFont = $this->baseFont ?? self::DEFAULT_BASE_FONT;
        if (!$baseFont instanceof Font || Font::System === $baseFont) {
            return [];
        }

        return [sprintf('fonts/%s-latin-wght-normal.woff2', self::BUNDLED_FONT_FILE_PREFIXES[$baseFont->name])];
    }

    /**
     * The URL of the Bunny Fonts stylesheet that loads the Google Fonts
     * families configured in Theme::font(), or null when all fonts are bundled.
     */
    public function getRemoteFontStylesheetUrl(): ?string
    {
        $families = [];
        foreach ([$this->baseFont, $this->monospaceFont] as $font) {
            if (\is_string($font)) {
                $families[] = sprintf('family=%s:wght@%s', str_replace(' ', '+', $font), self::REMOTE_FONT_WEIGHTS);
            }
        }

        if ([] === $families) {
            return null;
        }

        return sprintf('https://fonts.bunny.net/css2?%s&display=swap', implode('&', array_unique($families)));
    }

    /**
     * The CSS variable overrides to emit in the backend <head>, grouped by
     * target selector: 'common' variables apply to both color schemes
     * (':root, .ea-dark-scheme'), 'light' variables only to the light scheme
     * (':root', because the dark scheme redefines them on '.ea-dark-scheme')
     * and 'dark' variables only to '.ea-dark-scheme'.
     *
     * @return array{common: array<string, string>, light: array<string, string>, dark: array<string, string>}
     */
    public function getCssVariables(): array
    {
        $common = [];
        $light = [];
        $dark = [];

        if (null !== $this->primaryColor) {
            $common['--ea-primary'] = $this->primaryColor;
            $common['--ea-primary-foreground'] = $this->primaryForeground;
            $common += self::checkIcons($this->primaryForeground);
        }

        if (null !== $this->primaryOnSurface) {
            $light['--ea-primary-on-surface'] = $this->primaryOnSurface;
        }

        if (null !== $this->darkPrimaryColor) {
            $dark['--ea-primary'] = $this->darkPrimaryColor;
            $dark['--ea-primary-foreground'] = $this->darkPrimaryForeground;
            $dark += self::checkIcons($this->darkPrimaryForeground);
        }

        if (null !== $this->radius) {
            $common['--ea-radius'] = $this->radius;
        }

        if (null !== $this->spacing) {
            $common['--ea-spacing'] = $this->spacing;
        }

        if (null !== $this->grayRampPrefix) {
            foreach (self::GRAY_STEPS as $step) {
                $common['--gray-'.$step] = sprintf('var(%s%s)', $this->grayRampPrefix, $step);
            }
        }

        if (null !== $this->darkGrayRampPrefix) {
            foreach (self::GRAY_STEPS as $step) {
                $dark['--gray-'.$step] = sprintf('var(%s%s)', $this->darkGrayRampPrefix, $step);
            }
        }

        if (null !== $this->baseFont) {
            $common['--font-family-sans-serif'] = self::getFontFamily($this->baseFont, '--font-family-system-sans-serif');
            $fontWeights = $this->baseFont instanceof Font ? self::BUNDLED_FONT_WEIGHTS[$this->baseFont->name] ?? self::STATIC_FONT_WEIGHTS : self::STATIC_FONT_WEIGHTS;
            foreach (['normal', 'medium', 'semibold', 'bold'] as $i => $weightName) {
                $common['--font-weight-'.$weightName] = (string) $fontWeights[$i];
            }
        }

        if (null !== $this->baseFontWeight) {
            $common['--font-weight-normal'] = (string) $this->baseFontWeight;
        }

        if (null !== $this->baseFontFeatures) {
            $common['--font-feature-settings'] = [] === $this->baseFontFeatures ? 'normal' : implode(', ', $this->baseFontFeatures);
        }

        if (null !== $this->monospaceFont) {
            $common['--font-family-monospace'] = self::getFontFamily($this->monospaceFont, '--font-family-system-monospace');
        }

        return ['common' => $common, 'light' => $light, 'dark' => $dark];
    }

    public function hasCssVariables(): bool
    {
        $cssVariables = $this->getCssVariables();

        return [] !== $cssVariables['common'] || [] !== $cssVariables['light'] || [] !== $cssVariables['dark'];
    }

    /**
     * @return array<string, string>
     */
    private static function checkIcons(?string $foregroundColor): array
    {
        if (null === $foregroundColor) {
            return [];
        }

        $hexColor = ThemeColorCalculator::toHex($foregroundColor);
        // fully URL-encoded and unquoted, so the value has no quotes that the
        // HTML escaping of the template would break inside the <style> element
        $dataUri = static fn (string $svg): string => 'url(data:image/svg+xml,'.rawurlencode(sprintf($svg, $hexColor)).')';

        return [
            '--ea-primary-foreground-check-icon' => $dataUri(self::CHECK_ICON_SVG),
            '--ea-primary-foreground-radio-icon' => $dataUri(self::RADIO_ICON_SVG),
        ];
    }

    private static function getFontFamily(Font|string $font, string $systemFontsVariable): string
    {
        if (Font::System === $font) {
            return sprintf('var(%s)', $systemFontsVariable);
        }

        if ($font instanceof Font) {
            return sprintf('"%1$s Variable", "%1$s Variable Fallback", var(%2$s)', $font->value, $systemFontsVariable);
        }

        return sprintf('"%s", var(%s)', $font, $systemFontsVariable);
    }
}
