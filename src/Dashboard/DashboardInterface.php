<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

/**
 * This must be implemented by all backend dashboards.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface DashboardInterface
{
    public static function getConfig(): DashboardConfig;

    /**
     * @return MenuItemBuilder[]
     */
    public function getMenuItems(): iterable;
}
