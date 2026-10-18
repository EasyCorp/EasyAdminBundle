<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('EasyAdmin Tests');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fas fa-tags');
        yield MenuItem::linkTo(BlogPostCrudController::class, 'Blog Posts', 'fas fa-tags');

        yield MenuItem::section('Synthetic Tests');
        yield MenuItem::linkTo(Synthetic\FieldTestEntityCrudController::class, 'Field Tests', 'fas fa-flask');
        yield MenuItem::linkTo(Synthetic\FilterTestEntityCrudController::class, 'Filter Tests', 'fas fa-filter');
        yield MenuItem::linkTo(Synthetic\FormLayoutFieldsetsCrudController::class, 'Form Layout Tests', 'fas fa-th-large');
    }
}
