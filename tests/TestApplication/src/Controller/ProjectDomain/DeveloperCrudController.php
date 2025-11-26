<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ProjectDomain;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain\Developer;

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
