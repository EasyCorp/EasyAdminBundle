<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\GrayScale;
use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\CategoryCrudController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard controller for testing Dashboard::setTheme().
 */
#[AdminDashboard(routePath: '/customization_theme_admin', routeName: 'customization_theme_admin')]
class ThemeTestDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator
            ->setController(CategoryCrudController::class)
            ->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Theme Test')
            ->setTheme(Theme::new()
                ->primaryColor('#15803d', dark: 'oklch(0.6 0.2 150)')
                ->radius('lg')
                ->spacing('5px')
                ->grays(GrayScale::ZINC, dark: GrayScale::STONE)
                ->font(base: Font::Inter, monospace: Font::JetBrainsMono));
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('user-styles.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fas fa-list');
    }
}
