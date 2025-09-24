<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Component\HttpFoundation\Response;

#[AdminRoute('/api', 'api')] // this acts as a prefix since there are method routes
class PrefixedController
{
    #[AdminRoute('/users', 'users')]
    public function listUsers(): Response
    {
        return new Response('User list');
    }

    #[AdminRoute('/users/{id}', 'user_detail')]
    public function getUserDetail(string $id): Response
    {
        return new Response(sprintf('User detail: %s', $id));
    }
}
