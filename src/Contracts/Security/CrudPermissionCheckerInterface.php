<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Security;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * Checks whether the current user can execute an action of a CRUD controller
 * other than the current one (e.g. before linking to, or embedding, an entity
 * managed by another controller). It applies the same rules as EasyAdmin does
 * for AssociationField links: the target controller's Crud::setEntityPermission()
 * and Actions::setPermission() configuration.
 */
interface CrudPermissionCheckerInterface
{
    /**
     * @param AdminContext<object>   $context   the context of the page being rendered
     * @param EntityDto<object>|null $entityDto the target entity; when null, only the action-level permission is checked
     */
    public function isGranted(AdminContext $context, string $crudControllerFqcn, string $action, ?EntityDto $entityDto = null): bool;
}
