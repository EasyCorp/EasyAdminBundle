<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

/**
 * Dashboard protected by the 'access_control' rule of '^/admin' (ROLE_USER),
 * used to test the permissions and the per-user cache of widgets.
 */
#[AdminDashboard(routePath: '/admin/widgets', routeName: 'secured_widgets_admin', allowedControllers: [])]
class WidgetsSecuredDashboardController extends AbstractDashboardController
{
    public static int $userCacheComputations = 0;
    public static int $sharedCacheComputations = 0;

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Secured Widgets Dashboard');
    }

    public function configureWidgets(): iterable
    {
        yield StatWidget::new('Public stat')->setValue('Public value');
        yield StatWidget::new('Admin stat')->setValue('Admin value')->setPermission('ROLE_ADMIN')->addCssFiles('admin-widget.css');
        yield StatWidget::new('Lazy admin stat')->setValue('Lazy admin value')->setPermission('ROLE_ADMIN')->setLazy();
        yield StatWidget::new('User cached stat')
            ->setValue(static function (WidgetContext $context): string {
                ++self::$userCacheComputations;

                return sprintf('User cached %d for %s', self::$userCacheComputations, $context->getUser()?->getUserIdentifier());
            })
            ->setCache(60);
        yield StatWidget::new('Shared cached stat')
            ->setValue(static function (): string {
                ++self::$sharedCacheComputations;

                return sprintf('Shared cached %d', self::$sharedCacheComputations);
            })
            ->setCache(60, true);
    }
}
