<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;

#[AdminDashboard(routePath: '/widgets-errors-admin', routeName: 'widgets_errors_admin', allowedControllers: [])]
class WidgetsErrorsDashboardController extends AbstractDashboardController
{
    public const ERROR_MESSAGE = 'Failing widget 1 error.';

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Failing stat')->setValue(static fn () => throw new \RuntimeException(self::ERROR_MESSAGE));
        yield StatWidget::new('Working stat')->setValue('Working value');
        yield StatWidget::new('Failing cached stat')->setValue(static fn () => throw new \RuntimeException(self::ERROR_MESSAGE))->setCache(60);
    }
}
