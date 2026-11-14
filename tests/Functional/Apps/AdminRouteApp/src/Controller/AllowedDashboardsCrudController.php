<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD controller restricted to one dashboard with the 'allowedDashboards' option of the
 * class-level #[AdminRoute] attribute, and whose actions inherit or override that option.
 */
#[AdminRoute(allowedDashboards: [SecondDashboardController::class])]
class AllowedDashboardsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }

    #[AdminRoute('/{entityId}/show', 'show', allowedDashboards: [DashboardController::class])]
    public function detail(AdminContext $context): KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[AdminRoute('/inherited', 'inherited')]
    public function inherited(): Response
    {
        return new Response('Inherited');
    }

    #[AdminRoute('/only-first', 'only_first', allowedDashboards: [DashboardController::class])]
    public function onlyFirst(): Response
    {
        return new Response('Only First');
    }

    #[AdminRoute('/everywhere', 'everywhere', allowedDashboards: null)]
    public function everywhere(): Response
    {
        return new Response('Everywhere');
    }

    #[AdminRoute('/not-in-second', 'not_in_second', allowedDashboards: null, deniedDashboards: [SecondDashboardController::class])]
    public function notInSecond(): Response
    {
        return new Response('Not In Second');
    }
}
