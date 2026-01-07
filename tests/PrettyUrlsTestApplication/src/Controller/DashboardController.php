<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\PrettyUrlsTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\PrettyUrlsTestApplication\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\PrettyUrlsTestApplication\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

// needed for Symfony 5.4 - 8.0 compatibility (Attribute doesn't exist in 5.4 and
// Annotation doesn't exist in 8.0; both exist in the other versions)
if (class_exists('Symfony\Component\Routing\Annotation\Route') && !class_exists('Symfony\Component\Routing\Attribute\Route')) {
    // @phpstan-ignore-next-line class.notFound
    class_alias(\Symfony\Component\Routing\Annotation\Route::class, 'Symfony\Component\Routing\Attribute\Route');
}

use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin/pretty/urls', name: 'admin_pretty')]
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('EasyAdmin Tests');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::subMenu('Blog', 'fas fa-blog')->setSubItems([
            MenuItem::linkToCrud('Categories', 'fas fa-tags', Category::class),
            MenuItem::linkToCrud('Blog Posts', 'far fa-file-lines', BlogPost::class),
        ]);
    }
}
