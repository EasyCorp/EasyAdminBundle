<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Sorting;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\DemoEntity;

/**
 * CRUD controller for testing Crud::setDefaultSort() with multiple fields.
 */
class DefaultSortMultipleFieldsTestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DemoEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['name' => 'ASC', 'id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id');
        yield TextField::new('name');
    }
}
