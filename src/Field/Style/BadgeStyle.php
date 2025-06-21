<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Style;

final class BadgeStyle
{
    private ?string $textColor = null;

    public function __construct(
        private string $backgroundColor,
        ?string $textColor = null
    ) {
        if (!$this->isSupportedColor($this->backgroundColor)) {
            throw new \InvalidArgumentException(sprintf('The background color must be a full 6-digit hexadecimal color ("%s" given).', $this->backgroundColor));
        }

        if (null === $textColor) {
            $this->textColor = $this->computeTextColor($this->backgroundColor);
        } elseif (!$this->isSupportedColor($this->textColor)) {
            throw new \InvalidArgumentException(sprintf('The text color must be a full 6-digit hexadecimal color ("%s" given).', $this->textColor));
        }
    }

    public function toStyle(): string
    {
        return sprintf('background-color:%s; color:%s;', $this->backgroundColor, $this->textColor);
    }

    private function isSupportedColor(string $color): bool
    {
        return 1 === preg_match('/^#[0-9a-f]{6}$/iD', $color);
    }

    private function computeTextColor(string $bgColor): string
    {
        [$r, $g, $b] = [
            hexdec(substr($bgColor, 1, 2)),
            hexdec(substr($bgColor, 3, 2)),
            hexdec(substr($bgColor, 5, 2)),
        ];

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? '#000000' : '#FFFFFF';
    }
}
