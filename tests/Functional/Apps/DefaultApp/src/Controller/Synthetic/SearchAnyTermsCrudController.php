<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestEntity;

/**
 * CRUD controller for testing any-terms search mode.
 * Any search term can match (OR logic).
 * Only searches in author.name and author.email fields.
 *
 * @extends AbstractCrudController<SearchTestEntity>
 */
class SearchAnyTermsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setSearchFields(['author.name', 'author.email'])
            ->setSearchMode(SearchMode::ANY_TERMS);
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
