<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Security;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * Checks the permissions configured by a CRUD controller other than the current one
 * (Crud::setEntityPermission() and Actions::setPermission()), e.g. before rendering a
 * link to an entity managed by that controller.
 */
interface CrudPermissionCheckerInterface
{
    /**
     * @param AdminContext<object>   $context
     * @param EntityDto<object>|null $entityDto when null, only the action-level permission is checked
     */
    public function isGranted(AdminContext $context, string $crudControllerFqcn, string $action, ?EntityDto $entityDto = null): bool;
}
