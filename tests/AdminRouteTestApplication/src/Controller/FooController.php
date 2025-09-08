<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test case 2: Controller with class-level prefix and method-level routes.
 */
#[AdminRoute(
    path: '/foo',
    name: 'foo',
    allowedDashboards: [DashboardController::class]
)]
class FooController extends AbstractController
{
    #[AdminRoute(
        path: '/list',
        name: 'list'
    )]
    public function list(): Response
    {
        return new Response('Foo List');
    }

    #[AdminRoute(
        path: '/export/csv',
        name: 'export_csv',
        options: ['methods' => ['GET', 'POST']]
    )]
    public function exportCsv(): Response
    {
        return new Response('Export CSV');
    }

    /**
     * This method overrides the class-level dashboard restrictions.
     */
    #[AdminRoute(
        path: '/public-export',
        name: 'public_export',
        allowedDashboards: null,
    )]
    public function publicExport(): Response
    {
        return new Response('Public Export');
    }
}
