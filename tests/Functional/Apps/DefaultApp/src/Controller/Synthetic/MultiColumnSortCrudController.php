<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;

/**
 * CRUD controller for testing multi-column sorting.
 * Uses setDefaultSort() with multiple fields.
 *
 * @extends AbstractCrudController<SortTestEntity>
 */
class MultiColumnSortCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SortTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(100)
            ->setDefaultSort(['integerField' => 'ASC', 'textField' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('textField');
        yield IntegerField::new('integerField');
        yield DateTimeField::new('dateTimeField');
    }
}
