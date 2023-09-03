<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Security;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface PermissionInterface
{
    public const EA_ACCESS_ENTITY = 'EA_ACCESS_ENTITY';

    public const EA_EXECUTE_ACTION = 'EA_EXECUTE_ACTION';

    public const EA_VIEW_MENU_ITEM = 'EA_VIEW_MENU_ITEM';

    public const EA_VIEW_FIELD = 'EA_VIEW_FIELD';

    public const EA_EXIT_IMPERSONATION = 'EA_EXIT_IMPERSONATION';

    public static function exists(?string $permissionName): bool;
}
