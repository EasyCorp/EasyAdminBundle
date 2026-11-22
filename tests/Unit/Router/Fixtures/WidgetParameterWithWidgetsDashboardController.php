<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;

#[AdminDashboard(routePath: '/admin/{widgetName}', routeName: 'widget_parameter_widgets_admin')]
class WidgetParameterWithWidgetsDashboardController extends AbstractDashboardController
{
    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat')->setValue(1);
    }
}
