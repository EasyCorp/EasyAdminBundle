<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard controller that defines a route host, to test that this host is
 * inherited by all the routes generated for this dashboard.
 */
#[AdminDashboard(
    routePath: '/host-admin',
    routeName: 'host_admin',
    routeOptions: [
        'host' => 'backend.example.com',
    ],
)]
class HostDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('@EasyAdmin/page/dashboard.html.twig');
    }
}
