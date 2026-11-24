<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;

/**
 * Used by AutocompleteContextTest: its form is embedded in other forms and the
 * autocomplete entries of its "favoriteCategory" field are rendered as HTML.
 *
 * @extends AbstractCrudController<User>
 */
class FavoriteCategoryUserCrudController extends AbstractCrudController
{
    public const EMBEDDED_NEW_PAGE_NAME = 'new_user_inside_a_blog_post';

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $htmlTag = self::EMBEDDED_NEW_PAGE_NAME === $pageName ? 'em' : 'strong';

        return [
            TextField::new('name'),
            AssociationField::new('favoriteCategory')
                ->setCrudController(CategoryCrudController::class)
                ->autocomplete(
                    callback: static fn (Category $category): string => sprintf('<%1$s>%2$s</%1$s>', $htmlTag, htmlspecialchars($category->getName())),
                    renderAsHtml: true,
                ),
        ];
    }
}
