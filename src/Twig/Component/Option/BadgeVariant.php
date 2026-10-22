<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

enum BadgeVariant: string
{
    // these match the badge variants defined in the EasyAdmin theme
    // see assets/css/easyadmin-theme/badges.css
    case Light = 'light';
    case Solid = 'solid';
    case Outline = 'outline';
    case Dot = 'dot';

    // in previous versions, the variant was the color of the badge
    /** @deprecated since 5.7.0, use BadgeColor::Primary instead */
    case Primary = 'primary';
    /** @deprecated since 5.7.0, use BadgeColor::Secondary instead */
    case Secondary = 'secondary';
    /** @deprecated since 5.7.0, use BadgeColor::Success instead */
    case Success = 'success';
    /** @deprecated since 5.7.0, use BadgeColor::Danger instead */
    case Danger = 'danger';
    /** @deprecated since 5.7.0, use BadgeColor::Warning instead */
    case Warning = 'warning';
    /** @deprecated since 5.7.0, use BadgeColor::Info instead */
    case Info = 'info';
    /** @deprecated since 5.7.0, use BadgeColor::Dark instead */
    case Dark = 'dark';

    /**
     * @return list<self>
     */
    public static function styles(): array
    {
        return [self::Light, self::Solid, self::Outline, self::Dot];
    }

    public function isColor(): bool
    {
        return !\in_array($this, self::styles(), true);
    }

    public function asCssClass(): string
    {
        return $this->isColor() ? 'badge-'.$this->value : 'badge-variant-'.$this->value;
    }
}
