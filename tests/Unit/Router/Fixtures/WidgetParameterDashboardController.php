<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

/**
 * The path of this dashboard uses the placeholder that EasyAdmin adds to the route
 * that serves the contents of widgets, but the dashboard doesn't define widgets.
 */
#[AdminDashboard(routePath: '/admin/{widgetName}', routeName: 'widget_parameter_admin')]
class WidgetParameterDashboardController extends AbstractDashboardController
{
}
