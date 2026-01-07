<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class MenuItemBadgeDto
{
    // these are the names as the predefined styles used by Bootstrap 5
    public const PREDEFINED_STYLES = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

    /**
     * @param array<string, mixed> $htmlAttributes
     */
    public function __construct(private readonly mixed $content, private readonly string $style, private readonly array $htmlAttributes = [])
    {
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getCssClass(): string
    {
        return \in_array($this->style, self::PREDEFINED_STYLES, true) ? 'badge-'.$this->style : '';
    }

    public function getHtmlStyle(): string
    {
        return \in_array($this->style, self::PREDEFINED_STYLES, true) ? '' : $this->style;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }
}
