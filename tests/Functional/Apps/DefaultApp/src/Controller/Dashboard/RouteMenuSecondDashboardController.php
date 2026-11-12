<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/route_menu_second_admin', routeName: 'route_menu_second_admin')]
class RouteMenuSecondDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('EasyAdmin Tests');
    }

    public function configureMenuItems(): iterable
    {
        // the order matters: the link to the application route depends on the current dashboard
        // and the link to the admin route is the last URL generated for the menu of each request
        yield MenuItem::linkToRoute('App route', 'fa fa-file', 'custom_form_no_context');
        yield MenuItem::linkToRoute('Admin route', 'fa fa-file', 'route_menu_second_admin_custom_form_page');
    }
}
