<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test CRUD controller with multiple AdminRoute attributes on the same action.
 */
class MultipleRouteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    #[AdminRoute('/action1', 'action1')]
    #[AdminRoute('/action1-alt', 'action1_alt')]
    public function customAction1(): Response
    {
        return new Response('Custom Action 1');
    }

    #[AdminRoute('/action2/path1', 'action2_path1', options: ['methods' => ['GET']])]
    #[AdminRoute('/action2/path2', 'action2_path2', options: ['methods' => ['GET']])]
    #[AdminRoute('/action2/path3', 'action2_path3', options: ['methods' => ['GET', 'POST']])]
    public function customAction2(): Response
    {
        return new Response('Custom Action 2');
    }

    #[AdminRoute('/action3/{entityId}', 'action3')]
    #[AdminRoute('/action3-alt/{entityId}', 'action3_alt')]
    public function customAction3($entityId): Response
    {
        return new Response('Custom Action 3: '.$entityId);
    }

    #[AdminRoute('/action4/{entityId}', 'action4')]
    #[AdminRoute(path: '/action4-alt/{entityId}')]
    public function customAction4($entityId): Response
    {
        return new Response('Custom Action 4: '.$entityId);
    }

    #[AdminRoute('/action5', 'action5')]
    #[AdminRoute(name: 'action5_alt')]
    public function customAction5(): Response
    {
        return new Response('Custom Action 5');
    }

    #[AdminRoute('/action6', 'action6')]
    #[AdminRoute]
    public function customAction6(): Response
    {
        return new Response('Custom Action 6');
    }
}
