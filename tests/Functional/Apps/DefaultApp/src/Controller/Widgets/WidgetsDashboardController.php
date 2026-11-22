<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\ChartWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\ListItem;
use EasyCorp\Bundle\EasyAdminBundle\Widget\ListWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\ProgressWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\TwigWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetSection;

#[AdminDashboard(routePath: '/widgets-admin', routeName: 'widgets_admin', allowedControllers: [CategoryCrudController::class])]
class WidgetsDashboardController extends AbstractDashboardController
{
    // the test app doesn't run in debug mode, so the exceptions of widgets are not visible;
    // these counters tell if the data of a widget was computed and how many times
    public static int $hiddenWidgetComputations = 0;
    public static int $cachedWidgetComputations = 0;

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Widgets Dashboard');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fa fa-tags');
    }

    public function configureWidgets(): iterable
    {
        yield TwigWidget::new()
            ->setName('range')
            ->setTemplate('admin/widgets/range.html.twig', static fn (WidgetContext $context): array => ['range' => $context->getAttribute('range')])
            ->setPlacement(WidgetPlacement::Toolbar);

        yield StatWidget::new('Stat 1')->setValue(1234567)->setDelta(8.2)->setDescription('Description 1');
        yield StatWidget::new('Stat <em>2</em>')
            ->setName('stat-2')
            ->setValue(static fn (): string => '<b>Value 2</b>')
            ->setDelta(-3, false)
            ->setIcon('fa fa-users')
            ->setHelp('Help <em>2</em>')
            ->setLink('https://example.com/stat-2', 'Link <em>2</em>');
        yield StatWidget::new('Stat 3')->setValue(5)->formatValue(static fn (mixed $value): string => $value.' units')->setColumns(4)->setRows(2)->setCssClass('custom-widget-class');
        yield StatWidget::new('Hidden stat')
            ->setValue(static function (): int {
                ++self::$hiddenWidgetComputations;

                return 1;
            })
            ->addCssFiles('hidden-widget.css')
            ->setPermission('ROLE_NOBODY');
        yield StatWidget::new('Assets stat')
            ->setValue(1)
            ->addCssFiles('widget-styles.css')
            ->addJsFiles('widget-scripts.js')
            ->addHtmlContentsToHead('<meta name="widget-meta" content="Meta 1">');

        yield WidgetSection::new('Section 1', 'fa fa-chart-line')->setHelp('Section help <em>1</em>')->addCssClass('custom-section-class');
        yield ChartWidget::new('Chart 1')->setData(['Label 1' => 10, 'Label 2' => 25, '<b>Label 3</b>' => 0]);
        yield ChartWidget::new('Empty chart')->setData([]);
        yield ProgressWidget::new('Progress 1')->setValue(30)->setTarget(120);
        yield ListWidget::new('List 1')->setItems(static fn (): array => [
            ListItem::new('Item 1')->setDescription('Item description 1')->setValue(11)->setUrl('https://example.com/item-1')->setIcon('fa fa-file'),
            ListItem::new('<i>Item 2</i>'),
        ]);
        yield ListWidget::new('Empty list')->setItems([])->setEmptyMessage('Custom empty message');

        yield WidgetSection::new('Section 2')->setName('section-two');
        yield StatWidget::new('Lazy stat')->setValue(static fn (WidgetContext $context): string => 'Lazy value for range '.$context->getAttribute('range'))->setLazy();
        yield StatWidget::new('Refreshing stat')->setValue(77)->setRefreshInterval(30);
        yield StatWidget::new('Cached stat')
            ->setValue(static function (WidgetContext $context): string {
                ++self::$cachedWidgetComputations;

                return sprintf('Cached %d for range %s', self::$cachedWidgetComputations, $context->getAttribute('range'));
            })
            ->setCache(60);
        yield TwigWidget::new('Twig 1')->setTemplate('admin/widgets/twig_vars.html.twig', static fn (WidgetContext $context): array => ['var1' => 'Var 1', 'html_var' => '<b>Var 2</b>']);
        yield TwigWidget::new()->setName('notes')->setTemplate('admin/widgets/notes.html.twig', ['note' => 'Note 1'])->renderWithoutCard();

        yield WidgetSection::new('Hidden section')->setPermission('ROLE_NOBODY');
        yield StatWidget::new('Stat in hidden section')->setValue(static function (): int {
            ++self::$hiddenWidgetComputations;

            return 1;
        });
    }
}
