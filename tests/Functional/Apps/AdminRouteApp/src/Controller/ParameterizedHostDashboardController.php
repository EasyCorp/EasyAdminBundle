<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(
    routePath: '/param-admin',
    routeName: 'param_admin',
    routeOptions: [
        'host' => '{subdomain}',
        'defaults' => ['subdomain' => 'admin.example.com'],
        'requirements' => ['subdomain' => 'admin.*'],
    ],
)]
class ParameterizedHostDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('@EasyAdmin/page/dashboard.html.twig');
    }
}
