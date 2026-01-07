<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Component\HttpFoundation\Response;

class RepeatedRouteController
{
    #[AdminRoute('/route1/{id}', 'route1')]
    #[AdminRoute('/route2/{id}', 'route2')]
    public function twoRoutes(string $id): Response
    {
        return new Response(sprintf('ID: %s', $id));
    }

    #[AdminRoute('/multiple/route1', 'multiple_route1')]
    #[AdminRoute('/multiple/route2', 'multiple_route2')]
    #[AdminRoute('/multiple/route3', 'multiple_route3')]
    public function multipleRoutes(): Response
    {
        return new Response('Multiple routes to same action');
    }
}
