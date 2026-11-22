<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The #[IsGranted] attribute of its index() method only applies to POST requests, so
 * it doesn't protect the dashboard page nor the contents of its widgets, which are
 * requested with GET.
 */
#[AdminDashboard(routePath: '/admin/post-granted-widgets', routeName: 'secured_widgets_post_granted_admin', allowedControllers: [])]
class WidgetsPostGrantedDashboardController extends AbstractDashboardController
{
    #[IsGranted('ROLE_ADMIN', methods: ['POST'])]
    public function index(): Response
    {
        return parent::index();
    }

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue('Post granted value 1');
    }
}
