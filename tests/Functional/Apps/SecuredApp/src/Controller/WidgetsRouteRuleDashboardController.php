<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

/**
 * Dashboard protected by an 'access_control' rule that only matches its route name
 * (it requires ROLE_ADMIN), so the rule doesn't match the route that serves the
 * contents of its widgets.
 */
#[AdminDashboard(routePath: '/admin/route-rule-widgets', routeName: 'secured_widgets_route_rule_admin', allowedControllers: [])]
class WidgetsRouteRuleDashboardController extends AbstractDashboardController
{
    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue('Route rule value 1');
        yield StatWidget::new('Stat 2')
            ->setValue(static fn (WidgetContext $context): string => 'Locale '.$context->getRequest()->getLocale())
            ->setLazy();
    }
}
