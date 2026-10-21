<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test CRUD controller that overrides the built-in 'detail' action using the "{id}" placeholder
 * instead of the canonical "{entityId}" one. This verifies that the route generator accepts the
 * "{id}" alias for built-in entity actions (it used to require the literal "{entityId}" placeholder).
 */
class IdDetailCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }

    #[AdminRoute('/{id}', name: 'detail')]
    public function detail(AdminContext $context): KeyValueStore|Response
    {
        return parent::detail($context);
    }
}
