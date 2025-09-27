<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
enum ButtonVariant: string
{
    case Default = 'default';
    case Primary = 'primary';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
}
