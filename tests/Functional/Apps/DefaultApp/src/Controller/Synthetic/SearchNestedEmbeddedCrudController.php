<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestEntity;

/**
 * CRUD controller for testing search on association + embedded properties.
 * Searches in author.address.city (association to embedded).
 *
 * @extends AbstractCrudController<SearchTestEntity>
 */
class SearchNestedEmbeddedCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setSearchFields(['author.address.city']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('searchableTextField');
        yield AssociationField::new('author');
    }
}
