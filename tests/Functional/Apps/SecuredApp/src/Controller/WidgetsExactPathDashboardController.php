<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;

/**
 * Dashboard protected by an 'access_control' rule that only matches its exact path
 * ('^/admin/exact-path-widgets$' requires ROLE_ADMIN), so the rule doesn't match the
 * URL that serves the contents of its widgets.
 */
#[AdminDashboard(routePath: '/admin/exact-path-widgets', routeName: 'secured_widgets_exact_path_admin', allowedControllers: [])]
class WidgetsExactPathDashboardController extends AbstractDashboardController
{
    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue('Exact path value 1');
    }
}
