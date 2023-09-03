<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Security;

final class Permission implements PermissionInterface
{
    public static function exists(?string $permissionName): bool
    {
        if (null === $permissionName) {
            return false;
        }

        return \defined('self::'.$permissionName);
    }
}
