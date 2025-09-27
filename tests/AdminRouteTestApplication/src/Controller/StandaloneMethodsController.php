<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test case 4: Controller with only method-level routes (no class-level attribute).
 */
class StandaloneMethodsController extends AbstractController
{
    #[AdminRoute(
        path: '/standalone/action1',
        name: 'standalone_action1'
    )]
    public function action1(): Response
    {
        return new Response('Standalone Action 1');
    }

    #[AdminRoute(
        path: '/standalone/action2',
        name: 'standalone_action2',
        options: ['methods' => ['POST']]
    )]
    public function action2(): Response
    {
        return new Response('Standalone Action 2');
    }
}
