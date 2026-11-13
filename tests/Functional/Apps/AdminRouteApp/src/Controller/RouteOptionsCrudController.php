<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD controller that defines route options in the #[AdminRoute] attribute, both
 * at the class level (applied to all its routes) and at the action level.
 */
#[AdminRoute(options: [
    'host' => 'intranet.example.com',
    'schemes' => ['https'],
    'methods' => ['GET'],
    'defaults' => ['department' => 'sales', '_controller' => 'App\Controller\OtherController::index'],
])]
class RouteOptionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }

    #[AdminRoute('/inherited/{page<\d+>?1}', 'inherited')]
    public function inheritedOptions(): Response
    {
        return new Response('Inherited Options');
    }

    #[AdminRoute('/export/{format}', 'export', options: [
        'host' => 'reports.example.com',
        'methods' => ['GET'],
        'requirements' => ['format' => 'csv|xlsx'],
        'condition' => 'request.headers.get("X-Internal") == "1"',
    ])]
    public function overriddenOptions(string $format): Response
    {
        return new Response('Overridden Options: '.$format);
    }
}
