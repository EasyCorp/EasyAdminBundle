<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;

/**
 * This must be implemented by all backend dashboards.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface DashboardControllerInterface
{
    public function configureDashboard(): DashboardConfig;

    public function configureAssets(): AssetConfig;

    /**
     * @return MenuItemBuilder[]
     */
    public function getMenuItems(): iterable;
}
