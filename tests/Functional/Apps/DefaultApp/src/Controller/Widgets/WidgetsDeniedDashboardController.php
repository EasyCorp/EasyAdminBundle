<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetSection;

#[AdminDashboard(routePath: '/widgets-denied-admin', routeName: 'widgets_denied_admin', allowedControllers: [])]
class WidgetsDeniedDashboardController extends AbstractDashboardController
{
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Denied Widgets Dashboard');
    }

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Denied stat 1')->setValue(1)->setPermission('ROLE_NOBODY');

        yield WidgetSection::new('Denied section')->setPermission('ROLE_NOBODY');
        yield StatWidget::new('Denied stat 2')->setValue(2);
    }
}
