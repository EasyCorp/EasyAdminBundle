<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\Contract;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Widget\TwigWidget;

/**
 * Uses custom widgets built only with the public API (see EntityCountWidget and RowCountWidget).
 */
#[AdminDashboard(routePath: '/widgets-contract-admin', routeName: 'widgets_contract_admin', allowedControllers: [])]
class WidgetsContractDashboardController extends AbstractDashboardController
{
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Contract Widgets Dashboard');
    }

    public function configureWidgets(): iterable
    {
        yield TwigWidget::new()
            ->setName('contract-range')
            ->setTemplate('admin/widgets/contract/range_toolbar.html.twig', ['ranges' => ['7d', '30d']])
            ->setPlacement(WidgetPlacement::Toolbar);

        $plainCount = EntityCountWidget::new('Plain count')->setEntity(Category::class);
        yield $plainCount;

        yield (clone $plainCount)
            ->setName('cloned-count')
            ->setEntity(BlogPost::class)
            ->setColumns(6)
            ->addCssClass('cloned-class')
            ->addCssFiles(Asset::new('cloned-only.css')->package(EntityCountWidget::ASSET_PACKAGE));

        yield EntityCountWidget::new('Cached count')->setEntity(Category::class)->setCache(60);
        yield EntityCountWidget::new('Lazy count')->setEntity(Category::class)->setLazy();
        yield EntityCountWidget::new('Denied count')->setEntity(User::class)->setPermission('ROLE_NOBODY');

        yield RowCountWidget::new('Configured count')->setEntity(Category::class);
        yield RowCountWidget::new('Configured cached count')->setEntity(Category::class)->setCache(60);
        yield RowCountWidget::new('Configured lazy count')->setEntity(Category::class)->setLazy();
        yield RowCountWidget::new('Configured denied count')->setEntity(User::class)->setPermission('ROLE_NOBODY');
    }
}
