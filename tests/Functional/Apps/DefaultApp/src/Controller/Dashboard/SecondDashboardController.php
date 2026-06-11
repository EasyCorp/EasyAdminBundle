<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use Symfony\Component\HttpFoundation\Response;

/**
 * A second dashboard controller for testing multiple dashboards functionality:
 * - Independent dashboards with different configurations
 * - Different menu items per dashboard
 * - Different titles and branding.
 */
#[AdminDashboard(routePath: '/second_admin', routeName: 'second_admin')]
class SecondDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Second Dashboard');
    }

    public function configureMenuItems(): iterable
    {
        // this dashboard has a completely different menu structure
        yield MenuItem::linktoDashboard('Home', 'fa fa-house');

        yield MenuItem::section('Second Dashboard Content');

        // only Categories, not Blog Posts
        yield MenuItem::linkTo(CategoryCrudController::class, 'Manage Categories', 'fas fa-folder');

        // different external links
        yield MenuItem::section('Links');
        yield MenuItem::linkToUrl('Help Center', 'fas fa-question-circle', 'https://example.com/help');
    }
}
