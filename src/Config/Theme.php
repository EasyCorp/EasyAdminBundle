<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\FontFeature;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\GrayScale;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ThemeColor;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ThemeDto;
use EasyCorp\Bundle\EasyAdminBundle\Util\ThemeColorCalculator;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Theme
{
    private const RADIUS_PRESETS = [
        'none' => '0',
        'xs' => '0.125rem',
        'sm' => '0.1875rem',
        'md' => '0.25rem',
        'lg' => '0.375rem',
        'xl' => '0.5rem',
    ];

    // deliberately a narrow band (±25% around the default) and without a 'none'
    // preset: the spacing unit scales every padding, margin, gap and control
    // size of the backend linearly, so small changes already resize the whole UI
    private const SPACING_PRESETS = [
        'xs' => '0.09375rem',
        'sm' => '0.109375rem',
        'md' => '0.125rem',
        'lg' => '0.140625rem',
        'xl' => '0.15625rem',
    ];

    // the public names are the Tailwind/shadcn gray scale names; the values are
    // the prefixes of the color ramps defined in color-palette.css, whose legacy
    // names don't match ('neutral-gray' is zinc, NOT the 'neutral' scale)
    private const GRAY_RAMPS = [
        GrayScale::NEUTRAL => '--true-gray-',
        GrayScale::STONE => '--warm-gray-',
        GrayScale::ZINC => '--neutral-gray-',
        GrayScale::GRAY => '--cool-gray-',
        GrayScale::SLATE => '--blue-gray-',
        GrayScale::MAUVE => '--mauve-',
        GrayScale::OLIVE => '--olive-',
        GrayScale::MIST => '--mist-',
        GrayScale::TAUPE => '--taupe-',
    ];

    // the shadcn/ui color themes: each value is [light color, light foreground,
    // dark color, dark foreground]; the chromatic colors use the Tailwind 700
    // shade in the light scheme and the 800 shade in the dark one (yellow and
    // lime use lighter shades with dark text), and 'neutral' is almost black in
    // the light scheme and almost white in the dark one
    private const PRIMARY_COLOR_PRESETS = [
        ThemeColor::Neutral->value => ['oklch(0.205 0 0)', 'oklch(0.985 0 0)', 'oklch(0.922 0 0)', 'oklch(0.205 0 0)'],
        ThemeColor::Red->value => ['oklch(0.505 0.213 27.518)', 'oklch(0.971 0.013 17.38)', 'oklch(0.444 0.177 26.899)', 'oklch(0.971 0.013 17.38)'],
        ThemeColor::Orange->value => ['oklch(0.553 0.195 38.402)', 'oklch(0.98 0.016 73.684)', 'oklch(0.47 0.157 37.304)', 'oklch(0.98 0.016 73.684)'],
        ThemeColor::Amber->value => ['oklch(0.555 0.163 48.998)', 'oklch(0.987 0.022 95.277)', 'oklch(0.473 0.137 46.201)', 'oklch(0.987 0.022 95.277)'],
        ThemeColor::Yellow->value => ['oklch(0.852 0.199 91.936)', 'oklch(0.421 0.095 57.708)', 'oklch(0.795 0.184 86.047)', 'oklch(0.421 0.095 57.708)'],
        ThemeColor::Lime->value => ['oklch(0.841 0.238 128.85)', 'oklch(0.405 0.101 131.063)', 'oklch(0.768 0.233 130.85)', 'oklch(0.405 0.101 131.063)'],
        ThemeColor::Green->value => ['oklch(0.527 0.154 150.069)', 'oklch(0.982 0.018 155.826)', 'oklch(0.448 0.119 151.328)', 'oklch(0.982 0.018 155.826)'],
        ThemeColor::Emerald->value => ['oklch(0.508 0.118 165.612)', 'oklch(0.979 0.021 166.113)', 'oklch(0.432 0.095 166.913)', 'oklch(0.979 0.021 166.113)'],
        ThemeColor::Teal->value => ['oklch(0.511 0.096 186.391)', 'oklch(0.984 0.014 180.72)', 'oklch(0.437 0.078 188.216)', 'oklch(0.984 0.014 180.72)'],
        ThemeColor::Cyan->value => ['oklch(0.52 0.105 223.128)', 'oklch(0.984 0.019 200.873)', 'oklch(0.45 0.085 224.283)', 'oklch(0.984 0.019 200.873)'],
        ThemeColor::Sky->value => ['oklch(0.5 0.134 242.749)', 'oklch(0.977 0.013 236.62)', 'oklch(0.443 0.11 240.79)', 'oklch(0.977 0.013 236.62)'],
        ThemeColor::Blue->value => ['oklch(0.488 0.243 264.376)', 'oklch(0.97 0.014 254.604)', 'oklch(0.424 0.199 265.638)', 'oklch(0.97 0.014 254.604)'],
        ThemeColor::Indigo->value => ['oklch(0.457 0.24 277.023)', 'oklch(0.962 0.018 272.314)', 'oklch(0.398 0.195 277.366)', 'oklch(0.962 0.018 272.314)'],
        ThemeColor::Violet->value => ['oklch(0.491 0.27 292.581)', 'oklch(0.969 0.016 293.756)', 'oklch(0.432 0.232 292.759)', 'oklch(0.969 0.016 293.756)'],
        ThemeColor::Purple->value => ['oklch(0.496 0.265 301.924)', 'oklch(0.977 0.014 308.299)', 'oklch(0.438 0.218 303.724)', 'oklch(0.977 0.014 308.299)'],
        ThemeColor::Fuchsia->value => ['oklch(0.518 0.253 323.949)', 'oklch(0.977 0.017 320.058)', 'oklch(0.452 0.211 324.591)', 'oklch(0.977 0.017 320.058)'],
        ThemeColor::Pink->value => ['oklch(0.525 0.223 3.958)', 'oklch(0.971 0.014 343.198)', 'oklch(0.459 0.187 3.815)', 'oklch(0.971 0.014 343.198)'],
        ThemeColor::Rose->value => ['oklch(0.514 0.222 16.935)', 'oklch(0.969 0.015 12.422)', 'oklch(0.455 0.188 13.697)', 'oklch(0.969 0.015 12.422)'],
    ];

    private ?string $primaryColor = null;
    private ?string $primaryForeground = null;
    private ?string $darkPrimaryColor = null;
    private ?string $darkPrimaryForeground = null;
    private ?string $radius = null;
    private ?string $spacing = null;
    private ?string $grayRampPrefix = null;
    private ?string $darkGrayRampPrefix = null;
    private ?string $primaryOnSurface = null;
    private Font|string|null $baseFont = null;
    private Font|string|null $monospaceFont = null;
    private ?int $baseFontWeight = null;
    /** @var list<string>|null */
    private ?array $baseFontFeatures = null;

    private function __construct()
    {
    }

    public static function new(): self
    {
        return new self();
    }

    /**
     * Sets the accent color of the backend. Use one of the ThemeColor cases (or
     * their values: "neutral", "blue", "emerald", etc.) to apply a predefined color tuned for
     * both color schemes, or any color in the hexadecimal ("#15803d"), "rgb()",
     * "hsl()" or "oklch()" formats, without an alpha channel.
     *
     * The optional $dark argument defines a different primary color for the dark
     * color scheme (a good dark variant is usually retuned, not just lightened).
     * If not set, predefined colors use their own dark variant and custom colors
     * use the same color in both schemes. The color of the text/icons displayed
     * on top of primary-colored elements is computed automatically for custom
     * colors and predefined for the ThemeColor ones.
     */
    public function primaryColor(string|ThemeColor $color, string|ThemeColor|null $dark = null): self
    {
        $color = $color instanceof ThemeColor ? $color->value : $color;
        $dark = $dark instanceof ThemeColor ? $dark->value : $dark;
        $preset = self::PRIMARY_COLOR_PRESETS[$color] ?? null;

        if (null === $preset) {
            $this->primaryColor = self::validateColor($color);
            $this->primaryForeground = ThemeColorCalculator::foregroundFor($this->primaryColor);
        } else {
            [$this->primaryColor, $this->primaryForeground] = $preset;
        }

        // a light primary (e.g. yellow) is unreadable as text on the light
        // surfaces of the backend (active menu item, sorted column, etc.), so
        // those elements use the dark color displayed on top of it instead
        $this->primaryOnSurface = '#000' === ThemeColorCalculator::foregroundFor($this->primaryColor) ? $this->primaryForeground : null;

        if (null === $dark) {
            [$this->darkPrimaryColor, $this->darkPrimaryForeground] = null === $preset ? [null, null] : [$preset[2], $preset[3]];

            return $this;
        }

        $darkPreset = self::PRIMARY_COLOR_PRESETS[$dark] ?? null;
        if (null === $darkPreset) {
            $this->darkPrimaryColor = self::validateColor($dark);
            $this->darkPrimaryForeground = ThemeColorCalculator::foregroundFor($this->darkPrimaryColor);
        } else {
            [$this->darkPrimaryColor, $this->darkPrimaryForeground] = [$darkPreset[2], $darkPreset[3]];
        }

        return $this;
    }

    /**
     * Sets the base border radius from which all the border radius values of
     * the backend are derived. It accepts a CSS length in "px" or "rem" units
     * (e.g. "0.5rem") or one of these presets: "none", "xs", "sm", "md"
     * (the default look), "lg", "xl".
     */
    public function radius(string $value): self
    {
        $this->radius = self::RADIUS_PRESETS[$value] ?? self::validateLength($value, 'radius', array_keys(self::RADIUS_PRESETS));

        return $this;
    }

    /**
     * Sets the base spacing unit from which all the paddings, margins and gaps of
     * the backend are derived; it defines the density of the whole interface.
     * Controls (buttons, inputs, switches) only get one size smaller with values
     * below "md", and they don't get larger with values above it. It accepts a CSS
     * length in "px" or "rem" units or one of these presets: "xs", "sm", "md"
     * (the default look), "lg", "xl".
     */
    public function spacing(string $value): self
    {
        $this->spacing = self::SPACING_PRESETS[$value] ?? self::validateLength($value, 'spacing', array_keys(self::SPACING_PRESETS));

        return $this;
    }

    /**
     * Sets the gray color scale used by all the neutral surfaces, borders and
     * text colors of the backend. The optional $dark argument defines a
     * different gray scale for the dark color scheme (if not set, the same
     * scale is used in both schemes).
     *
     * Use one of the GrayScale constants: "neutral", "stone" (warm), "zinc",
     * "gray", "slate" (cool), "mauve" (purple tint), "olive" (green tint), "mist"
     * (blue-green tint) or "taupe" (brown tint). The default look uses "neutral"
     * in both the light and dark schemes.
     */
    public function grays(string $grayScale, ?string $dark = null): self
    {
        $this->grayRampPrefix = self::validateGrayScale($grayScale);
        $this->darkGrayRampPrefix = null === $dark ? null : self::validateGrayScale($dark);

        return $this;
    }

    /**
     * Sets the fonts of the backend: $base is used for all the text and
     * $monospace for code (e.g. the contents of code fields). By default, the
     * backend uses Inter for text and JetBrains Mono for code (pass
     * Font::System to use the fonts of the operating system instead).
     *
     * A Font enum case selects a font bundled with EasyAdmin, served by your
     * own application. A string is a Google Fonts family name, served through
     * Bunny Fonts (https://fonts.bunny.net), a privacy-friendly mirror of
     * Google Fonts; if the string is the name of a bundled font (e.g. 'Inter'),
     * the bundled font is used instead. A null value keeps the default font.
     *
     * $baseWeight is the font weight of regular text (by default, each bundled
     * font defines its own weights for regular, medium, semibold and bold text so
     * all of them look equally bold, and the other fonts use 400, 500, 600 and
     * 700). In-between weights (e.g. 430) only work as
     * expected with variable fonts, such as the bundled ones; with fonts that
     * have static weights, browsers use the 500 weight for values from 401 to 500.
     *
     * $features are the OpenType features of the base font, as FontFeature cases
     * or as the 4-character tags of the features to enable (e.g. 'ss01'). They
     * apply to all the text except code. Fonts only apply the features included
     * in their files (e.g. none of the bundled fonts include the slashed zero).
     * A null value applies no features; an empty array explicitly disables them.
     *
     * To use any other font (e.g. a font of your company), load it in a CSS file
     * added with addCssFile() and override the --font-family-sans-serif and
     * --font-family-monospace CSS variables.
     *
     * @param list<FontFeature|string>|null $features
     */
    public function font(Font|string|null $base = null, Font|string|null $monospace = null, ?int $baseWeight = null, ?array $features = null): self
    {
        $this->baseFont = null === $base ? null : self::validateFont($base, monospace: false);
        $this->monospaceFont = null === $monospace ? null : self::validateFont($monospace, monospace: true);
        $this->baseFontWeight = null === $baseWeight ? null : self::validateFontWeight($baseWeight);
        $this->baseFontFeatures = null === $features ? null : self::validateFontFeatures($features);

        return $this;
    }

    public function getAsDto(): ThemeDto
    {
        return new ThemeDto(
            $this->primaryColor,
            $this->primaryForeground,
            $this->darkPrimaryColor,
            $this->darkPrimaryForeground,
            $this->radius,
            $this->spacing,
            $this->grayRampPrefix,
            $this->darkGrayRampPrefix,
            $this->primaryOnSurface,
            $this->baseFont,
            $this->monospaceFont,
            $this->baseFontWeight,
            $this->baseFontFeatures,
        );
    }

    private static function validateFontWeight(int $weight): int
    {
        if ($weight < 100 || $weight > 900) {
            throw new \InvalidArgumentException(sprintf('The "%d" value given as the base weight of the font() method is not valid. Use a number between 100 and 900 (e.g. 400 for regular text).', $weight));
        }

        return $weight;
    }

    /**
     * @param array<mixed> $features
     *
     * @return list<string>
     */
    private static function validateFontFeatures(array $features): array
    {
        $settings = [];
        foreach ($features as $feature) {
            if ($feature instanceof FontFeature) {
                $settings[] = $feature->value;

                continue;
            }

            // the tags are emitted verbatim inside an inline <style> element, so this
            // validation also prevents CSS injection
            if (!\is_string($feature) || 1 !== preg_match('/^[A-Za-z0-9]{4}$/', $feature)) {
                throw new \InvalidArgumentException(sprintf('The "%s" value given in the features of the font() method is not valid. Use a FontFeature case (e.g. FontFeature::TabularNumbers) or the 4-character tag of an OpenType feature (e.g. "ss01").', \is_string($feature) ? $feature : get_debug_type($feature)));
            }

            $settings[] = sprintf('"%s" 1', $feature);
        }

        return array_values(array_unique($settings));
    }

    // remote font names are emitted verbatim in a URL and inside an inline
    // <style> element, so this validation is a security boundary against CSS and
    // URL injection: only letters, digits and single spaces are accepted, which
    // is enough for every Google Fonts family name
    private static function validateFont(Font|string $font, bool $monospace): Font|string
    {
        $argumentName = $monospace ? 'monospace' : 'base';

        if (\is_string($font)) {
            $fontName = trim($font);
            foreach (Font::cases() as $bundledFont) {
                if (0 === strcasecmp($bundledFont->value, preg_replace('/\s+/', ' ', $fontName))) {
                    $font = $bundledFont;
                    break;
                }
            }

            if (\is_string($font)) {
                if (1 !== preg_match('/^[A-Za-z0-9]+(?: [A-Za-z0-9]+)*$/', $fontName) || \strlen($fontName) > 64) {
                    throw new \InvalidArgumentException(sprintf('The "%s" value given as the %s font of the font() method is not a valid Google Fonts family name. Use the name of the font as displayed on fonts.google.com, which only contains letters, digits and spaces (e.g. "Open Sans").', $font, $argumentName));
                }

                return $fontName;
            }
        }

        if ($monospace && !\in_array($font, [Font::System, Font::JetBrainsMono], true)) {
            throw new \InvalidArgumentException(sprintf('The Font::%s font can\'t be used as the monospace font of the font() method because it\'s not a monospace font. Use Font::JetBrainsMono, Font::System or the name of a monospace font of Google Fonts (e.g. "Fira Code").', $font->name));
        }

        if (!$monospace && Font::JetBrainsMono === $font) {
            throw new \InvalidArgumentException('The Font::JetBrainsMono font can\'t be used as the base font of the font() method because it\'s a monospace font. Use it as the monospace font instead (e.g. font(monospace: Font::JetBrainsMono)).');
        }

        return $font;
    }

    private static function validateGrayScale(string $grayScale): string
    {
        if (!isset(self::GRAY_RAMPS[$grayScale])) {
            throw new \InvalidArgumentException(sprintf('The "%s" value given to the grays() method is not valid. It can only be one of: "%s".', $grayScale, implode('", "', array_keys(self::GRAY_RAMPS))));
        }

        return self::GRAY_RAMPS[$grayScale];
    }

    // the returned values are emitted verbatim inside an inline <style> element,
    // so this validation is a security boundary against CSS injection, not just
    // a usability check: only strictly anchored color grammars are accepted
    private static function validateColor(string $color): string
    {
        $color = trim($color);

        if (1 === preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return $color;
        }

        if (1 === preg_match('/^rgb\(\s*(\d{1,3})\s*(?:,\s*|\s+)(\d{1,3})\s*(?:,\s*|\s+)(\d{1,3})\s*\)$/i', $color, $matches)
            && (int) $matches[1] <= 255 && (int) $matches[2] <= 255 && (int) $matches[3] <= 255) {
            return $color;
        }

        if (1 === preg_match('/^hsl\(\s*(\d{1,3}(?:\.\d+)?)(?:deg)?\s*(?:,\s*|\s+)(\d{1,3}(?:\.\d+)?)%\s*(?:,\s*|\s+)(\d{1,3}(?:\.\d+)?)%\s*\)$/i', $color, $matches)
            && (float) $matches[1] <= 360 && (float) $matches[2] <= 100 && (float) $matches[3] <= 100) {
            return $color;
        }

        if (1 === preg_match('/^oklch\(\s*(\d{1,3}(?:\.\d+)?%|[01](?:\.\d+)?|\.\d+)\s+(\d(?:\.\d+)?|\.\d+)\s+(\d{1,3}(?:\.\d+)?)(?:deg)?\s*\)$/i', $color, $matches)) {
            $lightness = str_ends_with($matches[1], '%') ? (float) $matches[1] / 100 : (float) $matches[1];
            if ($lightness <= 1 && (float) $matches[2] <= 0.5 && (float) $matches[3] <= 360) {
                return $color;
            }
        }

        throw new \InvalidArgumentException(sprintf('The "%s" value given to the primaryColor() method is not a valid color. Use the hexadecimal ("#15803d"), "rgb()", "hsl()" or "oklch()" formats, without an alpha channel, or one of these predefined colors: "%s".', $color, implode('", "', array_keys(self::PRIMARY_COLOR_PRESETS))));
    }

    /**
     * @param list<string> $presetNames
     */
    private static function validateLength(string $value, string $optionName, array $presetNames): string
    {
        if (1 === preg_match('/^(?:0|(?:\d+|\d*\.\d+)(?:px|rem))$/', $value)) {
            return $value;
        }

        throw new \InvalidArgumentException(sprintf('The "%s" value given to the %s option is not valid. Use a CSS length in "px" or "rem" units (e.g. "0.5rem") or one of these presets: "%s".', $value, $optionName, implode('", "', $presetNames)));
    }
}
