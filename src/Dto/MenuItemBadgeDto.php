<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class MenuItemBadgeDto implements MenuItemBadgeDtoInterface
{
    // these are the names as the predefined styles used by Bootstrap 5
    public const PREDEFINED_STYLES = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

    private mixed $content;
    private string $style;

    public function __construct(mixed $content, string $style)
    {
        $this->content = $content;
        $this->style = $style;
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
}
