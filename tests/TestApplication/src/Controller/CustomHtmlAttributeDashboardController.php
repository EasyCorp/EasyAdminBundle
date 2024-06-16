<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CustomHtmlAttributeDashboardController extends AbstractDashboardController
{
    #[Route('/custom_html_attribute_admin', name: 'custom_html_attribute_admin')]
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
        yield MenuItem::linkToCrud('Categories', 'fas fa-tags', Category::class)->setHtmlAttribute(
            'test-attribute', 'test'
        );
        yield MenuItem::linkToCrud('Blog Posts', 'fas fa-tags', BlogPost::class)
            ->setHtmlAttribute('multi-test-one', 'test1')
            ->setHtmlAttribute('multi-test-two', 'test2')
            ->setBadge('0', 'secondary', [
                'badge-attr' => 'badge1',
            ])
        ;
    }
}
