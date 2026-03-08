<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test case 3: Controller with partial class-level configuration
 * The class only defines the route path prefix, not the name.
 */
#[AdminRoute(
    path: '/reports',
    allowedDashboards: [SecondDashboardController::class]
)]
class ReportsController extends AbstractController
{
    #[AdminRoute(
        path: '/sales',
        name: 'sales_report'
    )]
    public function salesReport(): Response
    {
        return new Response('Sales Report');
    }

    #[AdminRoute(
        path: '/inventory',
        name: 'inventory_report'
    )]
    public function inventoryReport(): Response
    {
        return new Response('Inventory Report');
    }
}
