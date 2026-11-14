<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\ActionsExtension;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Action\ActionsExtensionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ActionPermissions\ExtendedPermissionsCategoryCrudController;

class RestrictNewActionExtension implements ActionsExtensionInterface
{
    public function supports(AdminContext $context): bool
    {
        return ExtendedPermissionsCategoryCrudController::class === $context->getCrud()?->getControllerFqcn();
    }

    public function extend(Actions $actions, AdminContext $context): void
    {
        $actions->setPermissions([Action::NEW => 'ROLE_ADMIN']);
    }
}
