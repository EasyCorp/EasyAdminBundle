<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test case 1: Invokable controller with complete route definition.
 */
#[AdminRoute(
    path: '/custom-invokable',
    name: 'custom_invokable',
    options: ['defaults' => ['_locale' => 'en']]
)]
class InvokableController extends AbstractController
{
    public function __invoke(): Response
    {
        return new Response('Invokable Controller Response');
    }
}
