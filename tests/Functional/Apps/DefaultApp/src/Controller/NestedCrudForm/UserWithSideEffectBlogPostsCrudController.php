<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserWithSideEffectBlogPostsCrudController extends UserWithNestedBlogPostsCrudController
{
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'User Name');
        yield CollectionField::new('blogPosts', 'Blog Posts')
            ->renderExpanded(true)
            ->useEntryCrudForm(BlogPostWithSideEffectsCrudController::class);
    }
}
