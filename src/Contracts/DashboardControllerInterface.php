<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DashboardConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\UserMenuConfig;
use EasyCorp\Bundle\EasyAdminBundle\Dashboard\MenuItemBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This must be implemented by all backend dashboards.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface DashboardControllerInterface
{
    public function configureDashboard(): DashboardConfig;

    public function configureAssets(): AssetConfig;

    public function configureUserMenu(UserInterface $user): UserMenuConfig;

    /**
     * @return MenuItemBuilder[]
     */
    public function getMenuItems(): iterable;
}
