<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * Used by AutocompleteContextTest: embeds the form of BlogPostsAutocompleteUserCrudController,
 * which contains an autocomplete field.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class EmbeddedAuthorBlogPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            AssociationField::new('author')->renderAsEmbeddedForm(BlogPostsAutocompleteUserCrudController::class),
            AssociationField::new('publisher')->renderAsEmbeddedForm(BlogPostsAutocompleteUserCrudController::class, BlogPostsAutocompleteUserCrudController::EMBEDDED_NEW_PAGE_NAME),
        ];
    }
}
