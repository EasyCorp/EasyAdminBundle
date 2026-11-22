<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/widgets-welcome-admin', routeName: 'widgets_welcome_admin', allowedControllers: [])]
class WidgetsWelcomeDashboardController extends AbstractDashboardController
{
}
