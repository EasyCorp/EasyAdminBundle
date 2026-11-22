<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/widgets-custom-index-admin', routeName: 'widgets_custom_index_admin', allowedControllers: [])]
class WidgetsCustomIndexDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/widgets/custom_index.html.twig');
    }

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Stat 1')->setValue(1);
    }
}
