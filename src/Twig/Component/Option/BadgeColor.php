<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

enum BadgeColor: string
{
    // these match the badge colors defined in the EasyAdmin theme
    // see assets/css/easyadmin-theme/badges.css
    case Default = 'default';
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';
    case Light = 'light';
    case Dark = 'dark';

    public function asCssClass(): string
    {
        // 'default' is the neutral gray color; it reuses the 'secondary' class so
        // that the custom CSS styles applied to that class keep working
        return 'badge-'.(self::Default === $this ? self::Secondary->value : $this->value);
    }
}
