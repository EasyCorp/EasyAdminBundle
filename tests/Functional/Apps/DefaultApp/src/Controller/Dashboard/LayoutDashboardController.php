<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextDirection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard controller for testing layout options:
 * - renderContentMaximized
 * - renderSidebarMinimized
 * - setTextDirection (RTL)
 * - setFaviconPath.
 */
#[AdminDashboard(routePath: '/layout_admin', routeName: 'layout_admin')]
class LayoutDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Layout Test Dashboard')
            ->setFaviconPath('favicon-custom.ico')
            ->setTextDirection(TextDirection::RTL)
            ->renderContentMaximized()
            ->renderSidebarMinimized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fas fa-tags');
    }
}
