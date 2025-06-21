<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Style;

final class BadgeStyle
{
    private function __construct(private string $classes, private string $style)
    {
    }

    public static function fromBgColor(string $backgroundColor, ?string $textColor = null): self
    {
        if (!self::isSupportedColor($backgroundColor)) {
            throw new \InvalidArgumentException(sprintf('The background color must be a full 6-digit hexadecimal color ("%s" given).', $backgroundColor));
        }

        $classes = [];
        $styleProperties = ['background-color' => $backgroundColor];

        if (null === $textColor) {
            $classes[] = self::computeTextClass($backgroundColor);
        } elseif (self::isSupportedColor($textColor)) {
            $styleProperties['color'] = $textColor;
        } else {
            throw new \InvalidArgumentException(sprintf('The text color must be a full 6-digit hexadecimal color ("%s" given).', $textColor));
        }

        return new self(implode(' ', $classes), self::generateStyle($styleProperties));
    }

    public function getClasses(): string
    {
        return $this->classes;
    }

    public function getStyle(): string
    {
        return $this->style;
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

    private static function computeTextClass(string $backgroundColor): string
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
