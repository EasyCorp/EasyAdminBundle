<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Security;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * Checks the permissions configured by a CRUD controller other than the current one
 * (Crud::setEntityPermission() and Actions::setPermission()), e.g. before rendering a
 * link to an entity managed by that controller.
 */
interface CrudPermissionCheckerInterface
{
    /**
     * The entity permission is only checked when $entityDto wraps an entity instance;
     * otherwise (e.g. for the 'index' action) only the action permission is checked.
     *
     * @param AdminContextInterface<object> $context
     * @param EntityDto<object>|null        $entityDto
     *
     * @throws \InvalidArgumentException if $crudControllerFqcn is not a CRUD controller
     * @throws \LogicException           if the dashboard controller of $context cannot be resolved
     */
    public function isGranted(AdminContextInterface $context, string $crudControllerFqcn, string $action, ?EntityDto $entityDto = null): bool;
}
