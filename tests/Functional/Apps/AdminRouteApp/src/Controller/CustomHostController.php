<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller that defines its own route host, to test that it takes precedence
 * over the host defined by the dashboard.
 */
class CustomHostController extends AbstractController
{
    #[AdminRoute(
        path: '/custom-host',
        name: 'custom_host',
        options: ['host' => 'files.example.com']
    )]
    public function customHost(): Response
    {
        return new Response('Custom Host');
    }
}
