<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;

class BlogPostWithNestedCategoriesCrudController extends BlogPostNestedCrudController
{
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield CollectionField::new('categories')->useEntryCrudForm(CategoryCrudController::class);
    }
}
