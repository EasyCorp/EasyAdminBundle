<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Developer;

/**
 * @extends AbstractCrudController<Developer>
 */
class DeveloperCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Developer::class;
    }
}
