<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The paths of its entity actions use the "{id}" placeholder instead of "{entityId}".
 */
#[AdminDashboard(
    routePath: '/admin/id-placeholder',
    routeName: 'id_placeholder_admin',
    routes: [
        'detail' => ['routePath' => '/{id}'],
        'edit' => ['routePath' => '/{id}/edit'],
        'delete' => ['routePath' => '/{id}/delete'],
    ],
    allowedControllers: [CategoryCrudController::class],
)]
class IdPlaceholderDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }
}
