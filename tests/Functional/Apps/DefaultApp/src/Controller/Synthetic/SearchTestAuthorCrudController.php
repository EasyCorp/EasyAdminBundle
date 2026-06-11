<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestAuthor;

/**
 * CRUD controller used to test sorting by an embedded (embeddable) property.
 * The `address.city` field belongs to the embedded Address value object.
 *
 * @extends AbstractCrudController<SearchTestAuthor>
 */
class SearchTestAuthorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchTestAuthor::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('name');
        // embeddable property; sortable by default
        yield TextField::new('address.city');
    }
}
