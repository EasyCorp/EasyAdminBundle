<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard whose route only matches some requests (requirements of its placeholders,
 * host, schemes and condition). All the other routes of the dashboard must match only
 * those same requests.
 */
#[AdminDashboard(
    routePath: '/tenant-admin/{tenant}',
    routeName: 'tenant_admin',
    routeOptions: [
        'requirements' => ['tenant' => 'acme|globex', 'subdomain' => 'admin|backoffice'],
        'defaults' => ['subdomain' => 'admin'],
        'host' => '{subdomain}.example.com',
        'schemes' => ['https'],
        'condition' => 'request.headers.get("X-Admin") == "1"',
    ],
    allowedControllers: [MultipleRouteCrudController::class, RouteOptionsCrudController::class],
)]
class TenantDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return new Response('Tenant Dashboard');
    }
}
