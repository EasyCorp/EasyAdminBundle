<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD controller excluded from one dashboard with the 'deniedDashboards' option of the
 * class-level #[AdminRoute] attribute. The two options are inherited independently, so an
 * action that only sets 'allowedDashboards' is still excluded from the denied dashboard.
 */
#[AdminRoute(deniedDashboards: [DashboardController::class])]
class DeniedDashboardsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }

    #[AdminRoute('/still-denied', 'still_denied', allowedDashboards: [DashboardController::class])]
    public function stillDenied(): Response
    {
        return new Response('Still Denied');
    }

    #[AdminRoute('/not-denied', 'not_denied', deniedDashboards: null)]
    public function notDenied(): Response
    {
        return new Response('Not Denied');
    }
}
