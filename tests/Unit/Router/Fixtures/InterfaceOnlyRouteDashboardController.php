<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\InterfaceOnlyDashboardController;

/**
 * It doesn't extend AbstractDashboardController, so it has no renderWidget() method.
 */
#[AdminDashboard(routePath: '/plain-admin', routeName: 'plain_admin')]
class InterfaceOnlyRouteDashboardController extends InterfaceOnlyDashboardController
{
}
