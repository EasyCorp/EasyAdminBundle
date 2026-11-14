<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * The restricted form is embedded in an embedded form inside a collection entry.
 *
 * @extends AbstractCrudController<Category>
 */
class CategoryWithBlogPostsAndRestrictedAuthorFavoriteCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield CollectionField::new('blogPosts')->useEntryCrudForm(BlogPostWithAuthorAndRestrictedFavoriteCategoryCrudController::class);
    }
}
