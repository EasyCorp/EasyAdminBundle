<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Style;

final class BadgeStyle
{
    /**
     * @param array<string>         $classes
     * @param array<string, string> $style
     */
    private function __construct(private array $classes, private array $style)
    {
    }

    public static function new(): self
    {
        return new self([], []);
    }

    public function withBgColor(string $backgroundColor, bool $autoTextContrast = true): self
    {
        if (!self::isSupportedColor($backgroundColor)) {
            throw new \InvalidArgumentException(sprintf('The background color must be a full 6-digit hexadecimal color ("%s" given).', $backgroundColor));
        }

        $this->style['background-color'] = $backgroundColor;
        if ($autoTextContrast) {
            $this->classes[] = self::generateTextClassFromBackgroundColor($backgroundColor);
        }

        return $this;
    }

    public function withTextColor(string $textColor): self
    {
        if (!self::isSupportedColor($textColor)) {
            throw new \InvalidArgumentException(sprintf('The text color must be a full 6-digit hexadecimal color ("%s" given).', $textColor));
        }

        $this->style['color'] = $textColor;

        return $this;
    }

    public function getClasses(): string
    {
        return implode(' ', $this->classes);
    }

    public function getStyle(): string
    {
        return self::generateStyle($this->style);
    }

    /**
     * @param array<string, string> $properties
     */
    private static function generateStyle(array $properties): string
    {
        $style = [];
        foreach ($properties as $key => $value) {
            $style[] = sprintf('%s:%s;', $key, $value);
        }

        return implode(' ', $style);
    }

    private static function isSupportedColor(string $color): bool
    {
        return 1 === preg_match('/^#[0-9a-f]{6}$/iD', $color);
    }

    private static function generateTextClassFromBackgroundColor(string $backgroundColor): string
    {
        [$r, $g, $b] = [
            hexdec(substr($backgroundColor, 1, 2)),
            hexdec(substr($backgroundColor, 3, 2)),
            hexdec(substr($backgroundColor, 5, 2)),
        ];

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? 'text-dark' : 'text-light';
    }
}
