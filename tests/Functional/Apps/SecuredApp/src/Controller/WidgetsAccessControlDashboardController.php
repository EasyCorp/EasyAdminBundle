<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;

/**
 * Dashboard protected by the 'access_control' rule of '^/admin/secure' (ROLE_ADMIN).
 */
#[AdminDashboard(routePath: '/admin/secure/widgets', routeName: 'secured_widgets_access_control_admin', allowedControllers: [])]
class WidgetsAccessControlDashboardController extends AbstractDashboardController
{
    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue('Access control value 1');
    }
}
