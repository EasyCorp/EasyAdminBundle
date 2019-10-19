<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * This class is useful to extend your dashboard from it instead of implementing
 * the interface.
 */
abstract class AbstractDashboard extends AbstractController implements DashboardInterface
{
    public static function getConfig(): DashboardConfig
    {
        return DashboardConfig::new();
    }

    public function getMenuItems(): iterable
    {
        yield MenuItem::new('Dashboard', 'fa-home')->homepage();
    }
}
