<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
enum ButtonVariant: string
{
    case Solid = 'solid';
    case Outline = 'outline';
    case Light = 'light';
    case Ghost = 'ghost';
    case Link = 'link';

    /** @deprecated since 5.9.0, use ButtonColor::Default instead */
    case Default = 'default';
    /** @deprecated since 5.9.0, use ButtonColor::Primary instead */
    case Primary = 'primary';
    /** @deprecated since 5.9.0, use ButtonColor::Success instead */
    case Success = 'success';
    /** @deprecated since 5.9.0, use ButtonColor::Warning instead */
    case Warning = 'warning';
    /** @deprecated since 5.9.0, use ButtonColor::Danger instead */
    case Danger = 'danger';
    /** @deprecated since 5.9.0, use ButtonColor::Info instead */
    case Info = 'info';

    /**
     * @return list<self>
     */
    public static function styles(): array
    {
        return [self::Solid, self::Outline, self::Light, self::Ghost, self::Link];
    }

    public function isColor(): bool
    {
        return !\in_array($this, self::styles(), true);
    }

    /**
     * Returns the color represented by the deprecated color cases (and null for the styles).
     */
    public function asColor(): ?ButtonColor
    {
        return $this->isColor() ? ButtonColor::from($this->value) : null;
    }

    public function asCssClass(): string
    {
        // the deprecated color cases render the CSS class of their color
        return $this->asColor()?->asCssClass() ?? 'btn-'.$this->value;
    }
}
