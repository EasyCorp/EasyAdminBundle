<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A dashboard that implements the interface without extending AbstractDashboardController,
 * so it has neither configureWidgets() nor renderWidget().
 */
class InterfaceOnlyDashboardController implements DashboardControllerInterface
{
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new();
    }

    public function configureAssets(): Assets
    {
        return Assets::new();
    }

    public function configureMenuItems(): iterable
    {
        return [];
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

    public function index(): Response
    {
        return new Response();
    }
}
