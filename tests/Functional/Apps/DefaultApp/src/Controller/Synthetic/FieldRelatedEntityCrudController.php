<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldRelatedEntity;

/**
 * CrudController for FieldRelatedEntity (used for association field testing).
 *
 * @extends AbstractCrudController<FieldRelatedEntity>
 */
class FieldRelatedEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FieldRelatedEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name');
    }
}
