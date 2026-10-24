<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;

/**
 * Fixture showing the documented fix for the route-name collision (issue #7654): the
 * #[AdminRoute(name: ...)] attribute changes this controller's route-name segment to
 * "foo_batches", so its "delete" action generates "admin_foo_batches_delete" and no
 * longer collides with FooCrudController's "batchDelete" route.
 *
 * See FooCrudController for why these fixtures live outside "src/Controller/".
 */
#[AdminRoute(name: 'foo_batches')]
class FooBatchResolvedCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }
}
