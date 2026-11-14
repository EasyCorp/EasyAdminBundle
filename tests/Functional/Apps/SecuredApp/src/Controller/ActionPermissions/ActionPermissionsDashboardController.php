<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ActionPermissions;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used by ActionPermissionsMergeTest: the CRUD controllers of this dashboard
 * define more permissions on top of the one defined here.
 */
#[AdminDashboard('/admin/action-permissions', 'action_permissions_admin', allowedControllers: [
    SetPermissionsCategoryCrudController::class,
    LiftedPermissionCategoryCrudController::class,
    ExtendedPermissionsCategoryCrudController::class,
])]
class ActionPermissionsDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }
}
