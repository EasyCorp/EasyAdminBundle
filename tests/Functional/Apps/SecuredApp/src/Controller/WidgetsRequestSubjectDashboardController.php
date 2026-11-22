<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;

/**
 * Dashboard protected by an 'access_control' rule that only matches its exact path
 * and requires an attribute whose voter needs the request as the subject.
 */
#[AdminDashboard(routePath: '/admin/request-subject-widgets', routeName: 'secured_widgets_request_subject_admin', allowedControllers: [])]
class WidgetsRequestSubjectDashboardController extends AbstractDashboardController
{
    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue('Request subject value 1');
    }
}
