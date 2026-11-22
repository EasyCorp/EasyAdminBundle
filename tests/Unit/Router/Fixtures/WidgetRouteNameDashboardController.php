<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

/**
 * Its route name is the same as the name of the widget route of a dashboard whose route name is 'admin'.
 */
#[AdminDashboard(routePath: '/widgets-admin', routeName: 'admin_widget')]
class WidgetRouteNameDashboardController extends AbstractDashboardController
{
}
