<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/widgets-extended-admin', routeName: 'widgets_extended_admin', allowedControllers: [])]
class WidgetsExtendedDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/widgets/extended_dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Extended Widgets Dashboard');
    }

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue(1)->addCssFiles('extended-widget.css')->addJsFiles('extended-widget.js');
    }
}
