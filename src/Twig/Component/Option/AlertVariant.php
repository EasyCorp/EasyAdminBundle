<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

enum AlertVariant: string
{
    // these match the variants of Bootstrap 5.3 alerts
    // see https://getbootstrap.com/docs/5.3/components/alerts/
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';
    case Light = 'light';
    case Dark = 'dark';
    // these are not part of Bootstrap, but are commonly used in Symfony applications
    // see https://symfony.com/doc/current/session.html#flash-messages
    case Notice = 'notice';
    case Error = 'error';

    public function asBootstrapCssClass(): string
    {
        return 'alert-'.match ($this) {
            self::Notice => 'info',
            self::Error => 'danger',
            default => $this->value,
        };
    }
}
