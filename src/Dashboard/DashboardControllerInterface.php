<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\UserMenuConfig;
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
