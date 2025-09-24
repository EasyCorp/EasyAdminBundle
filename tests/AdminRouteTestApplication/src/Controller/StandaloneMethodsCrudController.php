<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test case 5: CRUD controller with only method-level routes (no class-level attribute).
 */
class StandaloneMethodsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    #[AdminRoute(
        path: '/crud/action1',
        name: 'crud_action1'
    )]
    public function action1(): Response
    {
        return new Response('Standalone CRUD Action 1');
    }

    #[AdminRoute(
        path: '/crud/action2',
        name: 'crud_action2',
        options: ['methods' => ['POST']]
    )]
    public function action2(): Response
    {
        return new Response('Standalone CRUD Action 2');
    }
}
