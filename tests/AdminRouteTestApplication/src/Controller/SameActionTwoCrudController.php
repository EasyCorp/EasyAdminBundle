<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test case 6: Method-level route in CRUD controller with the same name as other CRUD controller's method-level route.
 *
 * @see the related controller SameActionOneCrudController.php
 */
class SameActionTwoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    #[AdminRoute(path: '/same-action-name', name: 'same_action_name')]
    public function sameActionName(): Response
    {
        return new Response('Same action name');
    }
}
