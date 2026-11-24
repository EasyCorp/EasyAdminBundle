<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * Used by AutocompleteContextTest: renders each entry of a collection with the form of
 * CategoriesAutocompleteBlogPostCrudController, which contains an autocomplete field.
 *
 * @extends AbstractCrudController<User>
 */
class BlogPostsCollectionUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            CollectionField::new('blogPosts')->useEntryCrudForm(CategoriesAutocompleteBlogPostCrudController::class),
        ];
    }
}
