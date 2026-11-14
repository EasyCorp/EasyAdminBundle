<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller whose #[AdminRoute] attributes define their own requirements, schemes and
 * condition, which are combined with the restrictions of the dashboard route.
 */
#[AdminRoute(
    path: '/tenant-report/{year}',
    name: 'tenant_report',
    options: [
        'requirements' => ['year' => '\d{4}'],
        'condition' => 'request.query.has("token")',
    ],
    allowedDashboards: [TenantDashboardController::class],
)]
#[AdminRoute(
    path: '/tenant-report-http/{year}',
    name: 'tenant_report_http',
    options: [
        'requirements' => ['tenant' => 'initech'],
        'schemes' => ['http'],
    ],
    allowedDashboards: [TenantDashboardController::class],
)]
class TenantReportController extends AbstractController
{
    public function __invoke(string $year): Response
    {
        return new Response('Tenant Report: '.$year);
    }
}
