<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\RestrictedDashboard;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used by RestrictedDashboardControllersTest: only the CRUD controllers listed in
 * the "allowedControllers" option are available in this dashboard.
 */
#[AdminDashboard(routePath: '/admin/guest', routeName: 'admin_guest', allowedControllers: [
    GuestBlogPostCrudController::class,
    GuestUserCrudController::class,
])]
class GuestDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }
}
