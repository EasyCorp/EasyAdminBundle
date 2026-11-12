<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * CRUD controller for BlogPost whose author is edited with an embedded CRUD form,
 * which in turn embeds the CRUD form of the author's favorite category.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithEmbeddedAuthorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield AssociationField::new('author')
            ->renderAsEmbeddedForm(UserWithEmbeddedFavoriteCategoryCrudController::class);
    }
}
