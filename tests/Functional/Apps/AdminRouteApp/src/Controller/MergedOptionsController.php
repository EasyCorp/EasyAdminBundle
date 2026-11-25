<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller whose class and actions both define requirements and defaults in their
 * #[AdminRoute] attributes: the ones of the class are kept for the placeholders of the
 * class path, unless the action redefines them.
 */
#[AdminRoute(
    path: '/merged-options/{kind}',
    name: 'merged_options',
    options: [
        'requirements' => ['kind' => 'a|b'],
        'defaults' => ['section' => 'reports', 'page' => '1'],
    ],
)]
class MergedOptionsController extends AbstractController
{
    #[AdminRoute('/{id}', 'show', options: [
        'requirements' => ['id' => '\d+'],
        'defaults' => ['page' => '2'],
    ])]
    public function show(string $kind, string $id): Response
    {
        return new Response(sprintf('Merged Options: %s %s', $kind, $id));
    }

    #[AdminRoute('/redefined/{id}', 'redefined', options: [
        'requirements' => ['kind' => 'c'],
    ])]
    public function redefined(string $kind, string $id): Response
    {
        return new Response(sprintf('Redefined Options: %s %s', $kind, $id));
    }
}
