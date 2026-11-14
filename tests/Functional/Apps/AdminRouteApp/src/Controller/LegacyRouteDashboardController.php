<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Dashboard whose route is defined with the Symfony #[Route] attribute instead of the
 * #[AdminDashboard] attribute, so the requests with the legacy query string parameters
 * (crudControllerFqcn, crudAction) are dispatched without matching any admin route.
 */
#[AdminDashboard(allowedControllers: [AllowedDashboardsCrudController::class, DeniedDashboardsCrudController::class, MultipleRouteCrudController::class])]
class LegacyRouteDashboardController extends AbstractDashboardController
{
    #[Route('/legacy-admin', name: 'legacy_admin')]
    public function index(): Response
    {
        return new Response('Legacy Route Dashboard');
    }
}
