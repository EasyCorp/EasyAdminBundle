<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invokable controller that defines its own route host, to test that it takes
 * precedence over the host defined by the dashboard.
 */
#[AdminRoute(
    path: '/custom-host-invokable',
    name: 'custom_host_invokable',
    options: ['host' => 'files.example.com']
)]
class CustomHostInvokableController extends AbstractController
{
    public function __invoke(): Response
    {
        return new Response('Custom Host Invokable');
    }
}
