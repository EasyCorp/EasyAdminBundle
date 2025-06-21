<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Style;

final class BadgeStyle
{
    public const VALID_BADGE_TYPES = ['success', 'warning', 'danger', 'info', 'primary', 'secondary', 'light', 'dark'];

    /**
     * @param array<string>         $classes
     * @param array<string, string> $style
     */
    private function __construct(private array $classes, private array $style)
    {
    }

    public static function new(): self
    {
        return new self(['badge'], []);
    }

    public function withBgColor(string $backgroundColor, bool $autoTextContrast = true): self
    {
        if ($autoTextContrast) {
            $this->addClass(self::generateTextClassFromBackgroundColor($backgroundColor));
        }

        return $this->addStyle('background-color', $backgroundColor);
    }

    public function withTextColor(string $textColor): self
    {
        return $this->addStyle('color', $textColor);
    }

    /**
     * @param value-of<self::VALID_BADGE_TYPES> $type
     */
    public function withType(string $type): self
    {
        if (!\in_array($type, self::VALID_BADGE_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid badge type "%s". Allowed types are: "%s".', $type, implode(', ', self::VALID_BADGE_TYPES)));
        }

        return $this->addClass('badge-'.$type);
    }

    public function asPill(): self
    {
        return $this->addClass('badge-pill');
    }

    public function getClasses(): ?string
    {
        if ([] === $this->classes) {
            return null;
        }

        return implode(' ', $this->classes);
    }

    public function getStyle(): ?string
    {
        if ([] === $this->style) {
            return null;
        }

        return self::generateStyle($this->style);
    }

    private function addClass(string $class): self
    {
        $this->classes[] = $class;

        return $this;
    }

    private function addStyle(string $key, string $value): self
    {
        $this->style[$key] = $value;

        return $this;
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
        if (!self::isSupportedColor($backgroundColor)) {
            throw new \InvalidArgumentException(sprintf('Only full 6-digit hexadecimal color are supported to generate the appropriate text color ("%s" given).', $backgroundColor));
        }

        [$r, $g, $b] = [
            hexdec(substr($backgroundColor, 1, 2)),
            hexdec(substr($backgroundColor, 3, 2)),
            hexdec(substr($backgroundColor, 5, 2)),
        ];

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? 'text-dark' : 'text-light';
    }
}
