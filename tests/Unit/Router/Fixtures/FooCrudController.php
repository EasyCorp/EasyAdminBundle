<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;

/**
 * Fixture for the route-name collision test (issue #7654). Its built-in "batchDelete"
 * action generates the route name "admin_foo_batch_delete", which collides with the
 * built-in "delete" action of FooBatchCrudController.
 *
 * These fixtures live outside the functional app's "src/Controller/" directory on
 * purpose, so the shared AdminRouteApp kernel does not autoconfigure them (which would
 * make every functional route test fail on the very collision we want to test here).
 */
class FooCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }
}
