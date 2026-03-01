<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;

/**
 * @extends AbstractCrudController<SortTestEntity>
 */
class SortTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SortTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(100)
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('textField');
        yield IntegerField::new('integerField');
        yield DateTimeField::new('dateTimeField');

        // manyToOne association - sortable by the related entity's property
        yield AssociationField::new('manyToOneRelation')
            ->setSortProperty('name');

        // oneToMany association - can be sorted by count
        yield AssociationField::new('oneToManyRelations')
            ->hideOnForm();

        // manyToMany association - can be sorted by count
        yield AssociationField::new('manyToManyRelations');
    }
}
