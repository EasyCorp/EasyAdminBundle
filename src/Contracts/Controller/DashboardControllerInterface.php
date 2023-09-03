<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\AssetsInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\DashboardInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\FiltersInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenuInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This must be implemented by all backend dashboards.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface DashboardControllerInterface
{
    public function configureDashboard(): DashboardInterface;

    public function configureAssets(): AssetsInterface;

    /**
     * @return MenuItemInterface[]
     *
     * @psalm-return iterable<MenuItemInterface>
     */
    public function configureMenuItems(): iterable;

    public function configureUserMenu(UserInterface $user): UserMenuInterface;

    public function configureCrud(): Crud;

    public function configureActions(): Actions;

    public function configureFilters(): FiltersInterface;

    public function index(): Response;
}
