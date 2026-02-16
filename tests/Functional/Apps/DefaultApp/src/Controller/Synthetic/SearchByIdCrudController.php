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
 * CRUD controller for testing search by ID only.
 * Only the 'id' field is searchable.
 *
 * @extends AbstractCrudController<SearchTestEntity>
 */
class SearchByIdCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setSearchFields(['id']);
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
