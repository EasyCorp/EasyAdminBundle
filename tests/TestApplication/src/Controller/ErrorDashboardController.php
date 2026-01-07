<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

// needed for Symfony 5.4 - 8.0 compatibility (Attribute doesn't exist in 5.4 and
// Annotation doesn't exist in 8.0; both exist in the other versions)
if (class_exists('Symfony\Component\Routing\Annotation\Route') && !class_exists('Symfony\Component\Routing\Attribute\Route')) {
    // @phpstan-ignore-next-line class.notFound
    class_alias(\Symfony\Component\Routing\Annotation\Route::class, 'Symfony\Component\Routing\Attribute\Route');
}

use Symfony\Component\Routing\Attribute\Route;

class ErrorDashboardController extends AbstractDashboardController
{
    #[Route('/admin-error', name: 'admin_error')]
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('EasyAdmin Tests - Errors');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Error Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('ErrorFieldDoesNotBelongToAnyTabCrudController', null, ErrorFieldDoesNotBelongToAnyTabCrudController::class);
    }
}
