<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * The restricted form is nested in a collection entry inside an embedded form.
 *
 * @extends AbstractCrudController<User>
 */
class UserWithFavoriteCategoryAndRestrictedBlogPostsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield AssociationField::new('favoriteCategory')->renderAsEmbeddedForm(CategoryWithRestrictedBlogPostsCrudController::class);
    }
}
