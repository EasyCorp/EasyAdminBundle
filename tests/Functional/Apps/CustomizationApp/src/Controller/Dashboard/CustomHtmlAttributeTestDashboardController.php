<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\CategoryCrudController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(
    routePath: '/custom_html_attribute_test_admin',
    routeName: 'custom_html_attribute_test_admin'
)]
class CustomHtmlAttributeTestDashboardController extends AbstractDashboardController
{
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
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fas fa-tags')->setHtmlAttribute(
            'test-attribute', 'test'
        );
        yield MenuItem::linkTo(BlogPostCrudController::class, 'Blog Posts', 'fas fa-tags')
            ->setHtmlAttribute('multi-test-one', 'test1')
            ->setHtmlAttribute('multi-test-two', 'test2')
            ->setBadge('0', 'secondary', [
                'badge-attr' => 'badge1',
            ])
        ;
    }
}
