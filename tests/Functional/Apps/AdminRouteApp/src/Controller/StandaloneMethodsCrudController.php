<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Product;
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

    #[AdminRoute(
        path: '/crud/action3/{entityId}',
        name: 'crud_action3',
        options: [
            'requirements' => [
                'entityId' => '\d+',
            ],
            'options' => [
                'compiler_class' => 'Symfony\Component\Routing\RouteCompiler',
            ],
            'defaults' => [
                'foo' => 'bar',
            ],
            'host' => 'admin.example.com',
            'schemes' => 'https',
            'condition' => 'context.getMethod() in ["GET", "HEAD"]',
            'locale' => 'en',
            'format' => 'html',
            'utf8' => true,
            'stateless' => true,
        ]
    )]
    public function action3(): Response
    {
        return new Response('Standalone CRUD Action 3');
    }
}
