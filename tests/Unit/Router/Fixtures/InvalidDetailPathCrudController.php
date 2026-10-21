<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fixture CRUD controller that overrides the built-in 'detail' action with an invalid route
 * path: it contains neither the canonical "{entityId}" placeholder nor its "{id}" alias
 * ("{userId}" must not be matched as such).
 */
class InvalidDetailPathCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }

    #[AdminRoute('/{userId}', name: 'detail')]
    public function detail(AdminContext $context): KeyValueStore|Response
    {
        return parent::detail($context);
    }
}
