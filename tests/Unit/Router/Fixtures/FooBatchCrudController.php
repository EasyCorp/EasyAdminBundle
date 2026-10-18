<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;

/**
 * Fixture for the route-name collision test (issue #7654). Its built-in "delete" action
 * generates the route name "admin_foo_batch_delete", colliding with the built-in
 * "batchDelete" action of FooCrudController.
 *
 * See FooCrudController for why these fixtures live outside "src/Controller/".
 */
class FooBatchCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }
}
