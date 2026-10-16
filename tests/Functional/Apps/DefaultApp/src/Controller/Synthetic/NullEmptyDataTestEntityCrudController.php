<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ConstructorArgsTestEntity;

/**
 * Its createEntity() returns null but its form doesn't create the entity either,
 * which must result in an explicit error when submitting the "new" form.
 *
 * @extends AbstractCrudController<ConstructorArgsTestEntity>
 */
class NullEmptyDataTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ConstructorArgsTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setFormOptions(['empty_data' => null]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
    }

    public function createEntity(string $entityFqcn): ?object
    {
        return null;
    }
}
