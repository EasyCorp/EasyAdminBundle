<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * CRUD controller for BlogPost that embeds the CRUD form of the favorite category of its
 * author (a nested property path) using custom page names.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithEmbeddedAuthorFavoriteCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield AssociationField::new('author.favoriteCategory')
            ->renderAsEmbeddedForm(CategoryWithCreateOnlySlugCrudController::class, 'custom_new', 'custom_edit');
    }
}
