<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Locale;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard controller for testing locale switching functionality.
 */
#[AdminDashboard(routePath: '/locale_admin', routeName: 'locale_admin')]
class LocaleDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Locale Test Dashboard')
            ->setTranslationDomain('messages')
            ->setLocales([
                'en',    // Simple locale code
                'es',    // Spanish
                'fr',    // French
                Locale::new('de', 'Deutsch', 'fa fa-flag'), // Locale with custom label and icon
            ]);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fas fa-tags');
    }
}
