<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Product;

/**
 * Test CRUD controller whose class name triggers the suffix stripping bug
 * reported in https://github.com/EasyCorp/EasyAdminBundle/issues/7167.
 * The entity name "Controller" causes the auto-generated route to be empty
 * when using str_replace instead of suffix-only stripping.
 */
class ControllerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }
}
