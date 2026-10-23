<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserWithTwoLevelNestedBlogPostsCrudController extends UserWithNestedBlogPostsCrudController
{
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield CollectionField::new('blogPosts')
            ->useEntryCrudForm(BlogPostWithNestedCategoriesCrudController::class);
    }
}
