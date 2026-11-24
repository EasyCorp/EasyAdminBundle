<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;

/**
 * Used by AutocompleteContextTest: the autocomplete field is defined on a nested property.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class NestedFavoriteCategoryBlogPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            AssociationField::new('author.favoriteCategory')
                ->setCrudController(CategoryCrudController::class)
                ->autocomplete(
                    callback: static fn (Category $category): string => sprintf('<strong>%s</strong>', htmlspecialchars($category->getName())),
                    renderAsHtml: true,
                ),
        ];
    }
}
