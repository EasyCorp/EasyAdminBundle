<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
enum ButtonColor: string
{
    case Default = 'default';
    case Primary = 'primary';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
    case Info = 'info';

    public function asCssClass(): string
    {
        // 'default' is the neutral gray color; it reuses the 'secondary' class so
        // that the custom CSS styles applied to that class keep working
        return 'btn-'.(self::Default === $this ? 'secondary' : $this->value);
    }

    /**
     * The style used when a button doesn't define its variant: the primary color is
     * a solid button, the default color is an outline button and the other colors
     * are light (tinted) buttons.
     */
    public function getDefaultVariant(): ButtonVariant
    {
        return match ($this) {
            self::Primary => ButtonVariant::Solid,
            self::Default => ButtonVariant::Outline,
            default => ButtonVariant::Light,
        };
    }
}
