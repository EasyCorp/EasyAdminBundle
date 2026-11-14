<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * CRUD controller for User whose EDIT page lists the blog posts of the user with a
 * CRUD form that embeds this same CRUD form for the author of each blog post.
 *
 * @extends AbstractCrudController<User>
 */
class UserWithCyclicBlogPostsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextField::new('email')->onlyWhenCreating();

        if (Crud::PAGE_EDIT === $pageName) {
            yield CollectionField::new('blogPosts')
                ->useEntryCrudForm(BlogPostWithCyclicAuthorCrudController::class);
        }
    }
}
