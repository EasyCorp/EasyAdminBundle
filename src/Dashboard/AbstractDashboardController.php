<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This class is useful to extend your dashboard from it instead of implementing
 * the interface.
 */
abstract class AbstractDashboardController extends AbstractController implements DashboardControllerInterface
{
    public function configureDashboard(): DashboardConfig
    {
        return DashboardConfig::new();
    }

    public function configureAssets(): AssetConfig
    {
        return AssetConfig::new();
    }

    public function getMenuItems(): iterable
    {
        yield MenuItem::new('Dashboard', 'fa-home')->homepage();
    }

    /**
     * @Route("/admin", name="dashboard")
     */
    public function index(): Response
    {
        return $this->render('@EasyAdmin/layout.html.twig');
    }
}
