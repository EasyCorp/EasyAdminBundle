<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestEntity;

/**
 * CRUD controller for testing default all-terms search mode.
 * All search terms must match (AND logic).
 *
 * @extends AbstractCrudController<SearchTestEntity>
 */
class SearchAllTermsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPaginatorPageSize(15);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('searchableTextField');
        yield TextareaField::new('searchableContentField');
        yield TextField::new('nonSearchableField');
        yield AssociationField::new('author');
    }
}
