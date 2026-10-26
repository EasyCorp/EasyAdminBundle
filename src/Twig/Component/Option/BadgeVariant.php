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

    /**
     * Returns the variant that matches the given style and rejects unknown values
     * and the deprecated color cases, which are not visual styles of the badge.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromStyle(string|self $variant): self
    {
        $badgeVariant = $variant instanceof self ? $variant : self::tryFrom($variant);
        if (null === $badgeVariant || $badgeVariant->isColor()) {
            throw new \InvalidArgumentException(sprintf('The "%s" badge variant is not valid. Use one of these values: "%s".', $variant instanceof self ? $variant->value : $variant, implode('", "', array_map(static fn (self $variant): string => $variant->value, self::styles()))));
        }

        return $badgeVariant;
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
