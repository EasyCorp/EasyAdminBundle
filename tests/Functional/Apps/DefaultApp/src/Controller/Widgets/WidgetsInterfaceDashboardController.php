<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

/**
 * A dashboard that implements the interface without extending AbstractDashboardController
 * and without defining the configureWidgets() method.
 */
#[AdminDashboard(routePath: '/widgets-interface-admin', routeName: 'widgets_interface_admin', allowedControllers: [])]
class WidgetsInterfaceDashboardController implements DashboardControllerInterface
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->twig->render('admin/widgets/dashboard_without_layout.html.twig'));
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Interface Dashboard');
    }

    public function configureAssets(): Assets
    {
        return Assets::new();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return UserMenu::new();
    }

    public function configureCrud(): Crud
    {
        return Crud::new();
    }

    public function configureActions(): Actions
    {
        return Actions::new();
    }

    public function configureFilters(): Filters
    {
        return Filters::new();
    }
}
