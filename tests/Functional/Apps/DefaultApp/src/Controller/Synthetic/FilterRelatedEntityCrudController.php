<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterRelatedEntity;

/**
 * CRUD controller for FilterRelatedEntity, primarily used for autocomplete functionality in tests.
 *
 * @extends AbstractCrudController<FilterRelatedEntity>
 */
class FilterRelatedEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FilterRelatedEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name');
    }
}
