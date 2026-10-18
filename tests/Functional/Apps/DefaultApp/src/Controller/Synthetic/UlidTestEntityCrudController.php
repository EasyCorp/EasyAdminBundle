<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UlidTestEntity;

/**
 * Tests the CRUD actions and the search of an entity whose id is a Ulid.
 *
 * @extends AbstractCrudController<UlidTestEntity>
 */
class UlidTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UlidTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setSearchFields(['id', 'name']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name');
    }
}
