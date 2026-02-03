<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\BatchActionTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldRelatedEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterRelatedEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestEntity;

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
        yield MenuItem::linkToEntity(Category::class, 'Categories', 'fas fa-tags');
        yield MenuItem::linkToEntity(BlogPost::class, 'Blog Posts', 'fas fa-tags');

        // synthetic test entities
        yield MenuItem::section('Synthetic Tests');
        yield MenuItem::linkToEntity(FieldTestEntity::class, 'Field Tests', 'fas fa-flask');
        yield MenuItem::linkToEntity(FieldRelatedEntity::class, 'Field Related Entities', 'fas fa-link');
        yield MenuItem::linkToEntity(FilterTestEntity::class, 'Filter Tests', 'fas fa-filter');
        yield MenuItem::linkToEntity(FilterRelatedEntity::class, 'Filter Related Entities', 'fas fa-link');
        yield MenuItem::linkToEntity(FormTestEntity::class, 'Form Layout Tests', 'fas fa-th-large');
        yield MenuItem::linkToEntity(BatchActionTestEntity::class, 'Batch Action Tests', 'fas fa-tasks');
        yield MenuItem::linkToEntity(DefaultCrudTestEntity::class, 'Default CRUD Tests', 'fas fa-cog');
        yield MenuItem::linkToEntity(SearchTestEntity::class, 'Search Tests', 'fas fa-search');
        yield MenuItem::linkToEntity(ActionTestEntity::class, 'Action Tests', 'fas fa-mouse-pointer');
    }
}
