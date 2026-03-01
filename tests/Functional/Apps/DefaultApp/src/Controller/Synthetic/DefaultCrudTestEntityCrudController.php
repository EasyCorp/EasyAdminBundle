<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;

/**
 * Default CRUD controller with minimal configuration for testing out-of-the-box behavior.
 *
 * @extends AbstractCrudController<DefaultCrudTestEntity>
 */
class DefaultCrudTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DefaultCrudTestEntity::class;
    }

    // no custom configuration - uses all EasyAdmin defaults
}
