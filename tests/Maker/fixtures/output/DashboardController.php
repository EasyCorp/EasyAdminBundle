<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Purchase;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('ACME Backend');
    }

    public function configureCrud(): Crud
    {
        return Crud::new();
    }

    public function configureMenuItems(): iterable
    {
        $submenu1 = [
            MenuItem::linkToCrud('menu.product.list', 'fas fa-th-list', Product::class)->setDefaultSort(['createdAt' => 'DESC']),
            MenuItem::linkToCrud('menu.category', 'fas fa-tags', Category::class),
            MenuItem::linkToCrud('menu.product.add', 'fas fa-plus-circle', Product::class),
        ];

        yield MenuItem::subMenu('áéíóúäëïöüñ[]%@# menu.product', 'fas fa-shopping-basket')->setSubItems($submenu1);
        yield MenuItem::linkToCrud('menu.customer', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('menu.purchase', 'far fa-credit-card', Purchase::class)->setDefaultSort(['deliveryDate' => 'DESC']);

        yield MenuItem::section('menu.about', 'fas fa-folder-open');
        yield MenuItem::linkToUrl('menu.about.home', 'fas fa-home', 'https://github.com/EasyCorp/EasyAdminBundle')->setLinkTarget('_blank')->setLinkRel('noreferrer');
        yield MenuItem::linkToUrl('menu.about.docs', 'fas fa-book', 'https://symfony.com/doc/current/bundles/EasyAdminBundle')->setLinkTarget('_blank')->setLinkRel('noreferrer');
        yield MenuItem::linkToUrl('menu.about.issues', 'fab fa-github', 'https://github.com/EasyCorp/EasyAdminBundle/issues')->setLinkTarget('_blank')->setLinkRel('noreferrer');
    }
}
