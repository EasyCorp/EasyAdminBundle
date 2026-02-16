<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard controller for testing menu functionality:
 * - Menu items rendering
 * - Menu sections
 * - Submenus with hierarchy
 * - Active menu item highlighting
 * - Menu item badges.
 */
#[AdminDashboard(routePath: '/menu_admin', routeName: 'menu_admin')]
class MenuDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Menu Test Dashboard');
    }

    public function configureMenuItems(): iterable
    {
        // dashboard link (will be active on index page)
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');

        // section header
        yield MenuItem::section('Content Management');

        // regular CRUD link using linkTo()
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fas fa-tags');

        // CRUD link using linkTo() with explicit label and icon
        yield MenuItem::linkTo(BlogPostCrudController::class, 'Blog Posts', 'fas fa-newspaper')
            ->setBadge('New', 'success');

        // CRUD link using linkTo() with auto-derived label (no label/icon)
        yield MenuItem::linkTo(CategoryCrudController::class);

        // section with submenu, including a linkTo() submenu item
        yield MenuItem::section('Advanced');

        // submenu with nested items
        yield MenuItem::subMenu('Reports', 'fas fa-chart-bar')->setSubItems([
            MenuItem::linkToUrl('Sales Report', 'fas fa-dollar-sign', 'https://example.com/sales'),
            MenuItem::linkToUrl('Traffic Report', 'fas fa-traffic-light', 'https://example.com/traffic'),
        ]);

        // another submenu
        yield MenuItem::subMenu('Settings', 'fas fa-cog')->setSubItems([
            MenuItem::linkToUrl('General', 'fas fa-sliders-h', 'https://example.com/settings/general'),
            MenuItem::linkToUrl('Security', 'fas fa-shield-alt', 'https://example.com/settings/security'),
        ]);

        // external link section
        yield MenuItem::section('External Links');
        yield MenuItem::linkToUrl('Symfony', 'fab fa-symfony', 'https://symfony.com')
            ->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('EasyAdmin Docs', 'fas fa-book', 'https://symfony.com/bundles/EasyAdminBundle')
            ->setBadge('Docs', 'info');
    }
}
