<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard controller that tests all advanced routing options available
 * in the #[AdminDashboard] attribute's routeOptions parameter.
 */
#[AdminDashboard(
    routePath: '/advanced-admin/',
    routeName: 'advanced_admin',
    routeOptions: [
        'requirements' => [
            'foo' => '.*',
        ],
        'options' => [
            'compiler_class' => 'Symfony\Component\Routing\RouteCompiler',
        ],
        'defaults' => [
            'foo' => 'bar',
        ],
        'host' => 'admin.example.com',
        'methods' => ['GET', 'HEAD'],
        'schemes' => 'https',
        'condition' => 'context.getMethod() in ["GET", "HEAD"]',
        'locale' => 'en',
        'format' => 'html',
        'utf8' => true,
        'stateless' => true,
    ],
    allowedControllers: [],
)]
class AdvancedOptionsDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('@EasyAdmin/page/dashboard.html.twig');
    }
}
