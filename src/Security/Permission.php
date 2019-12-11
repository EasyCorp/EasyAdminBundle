<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Security;

final class Permission
{
    public const EA_VIEW_MENU_ITEM = 'EA_VIEW_MENU_ITEM';

    public static function exists(?string $permissionName): bool
    {
        if (null === $permissionName) {
            return false;
        }

        return defined('self::'.$permissionName);
    }
}
