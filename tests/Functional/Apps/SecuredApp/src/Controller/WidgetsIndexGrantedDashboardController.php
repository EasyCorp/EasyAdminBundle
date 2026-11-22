<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Dashboard protected by the #[IsGranted] attribute of its index() method (the
 * 'access_control' rule of its path only requires ROLE_USER).
 */
#[AdminDashboard(routePath: '/admin/index-granted-widgets', routeName: 'secured_widgets_index_granted_admin', allowedControllers: [])]
class WidgetsIndexGrantedDashboardController extends AbstractDashboardController
{
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return parent::index();
    }

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue('Index granted value 1');
    }
}
