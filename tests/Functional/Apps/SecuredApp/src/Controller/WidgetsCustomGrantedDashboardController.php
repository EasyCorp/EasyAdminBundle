<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security\RequiresAdminRole;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard protected by a custom attribute that extends #[IsGranted], applied to
 * its index() method.
 */
#[AdminDashboard(routePath: '/admin/custom-granted-widgets', routeName: 'secured_widgets_custom_granted_admin', allowedControllers: [])]
class WidgetsCustomGrantedDashboardController extends AbstractDashboardController
{
    #[RequiresAdminRole]
    public function index(): Response
    {
        return parent::index();
    }

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue('Custom granted value 1');
    }
}
