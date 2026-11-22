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
 * so it has no route to load the contents of lazy widgets.
 */
#[AdminDashboard(routePath: '/widgets-interface-lazy-admin', routeName: 'widgets_interface_lazy_admin', allowedControllers: [])]
class WidgetsInterfaceLazyDashboardController implements DashboardControllerInterface
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
        yield StatWidget::new('Lazy stat')->setValue(1)->setLazy();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Interface Lazy Dashboard');
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
