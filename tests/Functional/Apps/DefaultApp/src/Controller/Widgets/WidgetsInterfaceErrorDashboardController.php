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
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

/**
 * A dashboard that implements the interface without extending AbstractDashboardController,
 * so it has no route to load again the contents of a widget that fails.
 */
#[AdminDashboard(routePath: '/widgets-interface-error-admin', routeName: 'widgets_interface_error_admin', allowedControllers: [])]
class WidgetsInterfaceErrorDashboardController implements DashboardControllerInterface
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->twig->render('admin/widgets/dashboard_without_layout.html.twig'));
    }

    /**
     * @return iterable<StatWidget>
     */
    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Failing stat')->setValue(static fn () => throw new \RuntimeException('The widget failed.'));
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Interface Error Dashboard');
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
