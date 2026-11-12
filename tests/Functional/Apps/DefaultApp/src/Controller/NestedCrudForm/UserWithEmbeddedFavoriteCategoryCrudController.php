<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * CRUD controller for User whose email can only be set when creating the user and
 * whose favorite category is edited with an embedded CRUD form.
 *
 * @extends AbstractCrudController<User>
 */
class UserWithEmbeddedFavoriteCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextField::new('email')->onlyWhenCreating();
        yield AssociationField::new('favoriteCategory')
            ->renderAsEmbeddedForm(CategoryWithCreateOnlySlugCrudController::class);
    }
}
