<?php

namespace App\Controller\Admin;

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
        yield MenuItem::linkToCrud('menu.customer', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('menu.purchase', 'fa fa-credit-card', Purchase::class)->setDefaultSort(['deliveryDate' => 'DESC']);

        yield MenuItem::section('menu.about', 'fa fa-folder-open');
    }
}
