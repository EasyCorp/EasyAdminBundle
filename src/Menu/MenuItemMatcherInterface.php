<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemMatcherInterface as ContractMenuItemMatcherInterface;

trigger_deprecation(
    'easycorp/easyadmin-bundle',
    '4.8.1',
    'The "%s" class is deprecated and it will be removed in EasyAdmin 5.0.0, use "%s" instead.',
    MenuItemMatcherInterface::class, ContractMenuItemMatcherInterface::class
);

class_exists(ContractMenuItemMatcherInterface::class);

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated since EasyAdmin 4.8.1, to be removed in 5.0, use {@link ContractMenuItemMatcherInterface} instead
     */
    class MenuItemMatcherInterface
    {
    }
}
