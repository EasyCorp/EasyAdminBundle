<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetSection;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/widgets-components-admin', routeName: 'widgets_components_admin', allowedControllers: [])]
class WidgetsComponentsDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $template = 'unknown-widget' === $this->container->get('request_stack')->getCurrentRequest()?->query->get('template')
            ? 'admin/widgets/components_unknown_widget.html.twig'
            : 'admin/widgets/components.html.twig';

        return $this->render($template);
    }

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Toolbar stat')->setValue('Toolbar value')->setPlacement(WidgetPlacement::Toolbar);
        yield StatWidget::new('Single stat')->setValue('Single value');
        yield StatWidget::new('Denied stat')->setValue('Denied value')->setPermission('ROLE_NOBODY');

        yield WidgetSection::new('Section A');
        yield StatWidget::new('Stat A')->setValue('Value A');

        yield WidgetSection::new('Section B');
        yield StatWidget::new('Stat B')->setValue('Value B');
    }
}
