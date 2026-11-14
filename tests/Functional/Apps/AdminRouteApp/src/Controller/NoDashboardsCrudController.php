<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD controller that allows no dashboard, so none of its routes are generated.
 */
#[AdminRoute(allowedDashboards: [])]
class NoDashboardsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    #[AdminRoute('/custom', 'custom')]
    public function custom(): Response
    {
        return new Response('Custom');
    }
}
