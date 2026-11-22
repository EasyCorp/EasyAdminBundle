<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Dashboard protected by the #[IsGranted] attribute of its class (the 'access_control'
 * rule of its path only requires ROLE_USER).
 */
#[AdminDashboard(routePath: '/admin/granted-widgets', routeName: 'secured_widgets_is_granted_admin', allowedControllers: [])]
#[IsGranted('ROLE_ADMIN')]
class WidgetsIsGrantedDashboardController extends AbstractDashboardController
{
    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue('Is granted value 1');
    }
}
