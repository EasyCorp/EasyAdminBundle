<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Util;

/**
 * Computes the readable foreground color (black or white) to display on top of
 * a given background color; the theme uses it to derive --ea-primary-foreground.
 *
 * @internal
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ThemeColorCalculator
{
    // the WCAG contrast crossover: above this luminance, black text yields more
    // contrast than white text (derived from (L + 0.05)² = 1.05 × 0.05)
    private const BLACK_FOREGROUND_LUMINANCE_THRESHOLD = 0.179;

    public static function foregroundFor(string $color): string
    {
        return self::relativeLuminance($color) > self::BLACK_FOREGROUND_LUMINANCE_THRESHOLD ? '#000' : '#fff';
    }

    /**
     * Returns the WCAG 2.x relative luminance, a value between 0.0 (black) and
     * 1.0 (white). $color must be a color in one of the formats accepted by
     * the Theme config class: hexadecimal, rgb(), hsl() or oklch().
     */
    public static function relativeLuminance(string $color): float
    {
        [$r, $g, $b] = self::toLinearRgb($color);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Returns the given color in the 6-digit hexadecimal format (e.g. "#1a2b3c"),
     * needed where CSS colors are not supported (e.g. inside data URIs).
     */
    public static function toHex(string $color): string
    {
        return '#'.implode('', array_map(
            static fn (float $channel): string => sprintf('%02x', (int) round(self::delinearize($channel) * 255)),
            self::toLinearRgb($color),
        ));
    }

    /**
     * @return array{float, float, float} linear-light sRGB channels in [0, 1]
     */
    private static function toLinearRgb(string $color): array
    {
        $color = strtolower(trim($color));

        if (str_starts_with($color, '#')) {
            return array_map(self::linearize(...), self::hexToRgb($color));
        }

        preg_match_all('/[\d.]+%?/', $color, $matches);
        $components = $matches[0];

        if (str_starts_with($color, 'rgb(')) {
            return array_map(self::linearize(...), [
                (float) $components[0] / 255,
                (float) $components[1] / 255,
                (float) $components[2] / 255,
            ]);
        }

        if (str_starts_with($color, 'hsl(')) {
            $rgb = self::hslToRgb(
                (float) $components[0],
                (float) $components[1] / 100,
                (float) $components[2] / 100,
            );

            return array_map(self::linearize(...), $rgb);
        }

        if (str_starts_with($color, 'oklch(')) {
            $lightness = str_ends_with($components[0], '%') ? (float) $components[0] / 100 : (float) $components[0];

            return self::oklchToLinearRgb($lightness, (float) $components[1], (float) $components[2]);
        }

        throw new \InvalidArgumentException(sprintf('Unsupported color format: "%s".', $color));
    }

    private static function linearize(float $channel): float
    {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }

    private static function delinearize(float $channel): float
    {
        return $channel <= 0.0031308 ? $channel * 12.92 : 1.055 * $channel ** (1 / 2.4) - 0.055;
    }

    /**
     * @return array{float, float, float} gamma-encoded sRGB channels in [0, 1]
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = substr($hex, 1);
        if (3 === \strlen($hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    /**
     * @param float $hue        in [0, 360]
     * @param float $saturation in [0, 1]
     * @param float $lightness  in [0, 1]
     *
     * @return array{float, float, float} gamma-encoded sRGB channels in [0, 1]
     */
    private static function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $secondary = $chroma * (1 - abs(fmod($hue / 60, 2) - 1));
        $offset = $lightness - $chroma / 2;

        [$r, $g, $b] = match (true) {
            $hue < 60 => [$chroma, $secondary, 0],
            $hue < 120 => [$secondary, $chroma, 0],
            $hue < 180 => [0, $chroma, $secondary],
            $hue < 240 => [0, $secondary, $chroma],
            $hue < 300 => [$secondary, 0, $chroma],
            default => [$chroma, 0, $secondary],
        };

        return [$r + $offset, $g + $offset, $b + $offset];
    }

    /**
     * OKLCH → OKLab → linear sRGB, using the standard OKLab matrices
     * (https://bottosson.github.io/posts/oklab/). Out-of-gamut colors are
     * clamped, which is enough for a luminance estimation.
     *
     * @return array{float, float, float} linear-light sRGB channels in [0, 1]
     */
    private static function oklchToLinearRgb(float $lightness, float $chroma, float $hue): array
    {
        $a = $chroma * cos(deg2rad($hue));
        $b = $chroma * sin(deg2rad($hue));

        $l = ($lightness + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m = ($lightness - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s = ($lightness - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

        return [
            max(0.0, min(1.0, 4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s)),
            max(0.0, min(1.0, -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s)),
            max(0.0, min(1.0, -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s)),
        ];
    }
}
