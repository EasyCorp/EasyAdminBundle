<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Security;

final class Permission
{
    public const EA_VIEW_ACTION = 'EA_VIEW_ACTION';
    public const EA_VIEW_MENU_ITEM = 'EA_VIEW_MENU_ITEM';
    public const EA_VIEW_PROPERTY = 'EA_VIEW_PROPERTY';
    public const EA_VIEW_ENTITY = 'EA_VIEW_ENTITY';
    public const EA_EXIT_IMPERSONATION = 'EA_EXIT_IMPERSONATION';

    public static function exists(?string $permissionName): bool
    {
        if (null === $permissionName) {
            return false;
        }

        return defined('self::'.$permissionName);
    }
}
