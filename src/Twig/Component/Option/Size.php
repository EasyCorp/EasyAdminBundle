<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

/**
 * Shared size token for EasyAdmin components ('sm'/'md'/'lg' scale). 'md' is the
 * base size and emits no CSS class, except in components whose base size is inherited
 * from the surrounding text (e.g. Icon), which emit a class for every value. Unlike
 * the radius scale, size CSS classes are component-specific (e.g. 'ea-switch-sm')
 * because size affects per-component dimensions; each component builds its own
 * class from the enum value and may support only a subset of the scale.
 */
enum Size: string
{
    case Small = 'sm';
    case Medium = 'md';
    case Large = 'lg';
}
