<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Association;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\InverseOneToOneTestEntity;

/**
 * Index page of an entity with the inverse side of a one-to-one association, to test that
 * the to-one associations displayed on the page are still loaded with a single query.
 *
 * @extends AbstractCrudController<InverseOneToOneTestEntity>
 */
class InverseOneToOneCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InverseOneToOneTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            AssociationField::new('author'),
        ];
    }
}
