<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Entity\Product;

/**
 * Test CRUD controller that overrides the route names and paths of built-in CRUD actions.
 * This tests that when a built-in action has a custom route, only the custom route is generated
 * and the default route is NOT generated (avoiding duplicates).
 */
class BuiltInActionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    // override the 'index' action with a custom route name
    #[AdminRoute(name: 'list')]
    public function index(AdminContext $context)
    {
        return parent::index($context);
    }

    // override the 'new' action with a custom route name and path
    #[AdminRoute(path: '/create', name: 'create')]
    public function new(AdminContext $context)
    {
        return parent::new($context);
    }

    // override the 'edit' action with a custom route name
    #[AdminRoute(name: 'update')]
    public function edit(AdminContext $context)
    {
        return parent::edit($context);
    }

    // override the 'detail' action with a custom route name
    #[AdminRoute(name: 'show')]
    public function detail(AdminContext $context)
    {
        return parent::detail($context);
    }

    // keep 'delete' action with default route (no override) to test mixed scenarios
}
