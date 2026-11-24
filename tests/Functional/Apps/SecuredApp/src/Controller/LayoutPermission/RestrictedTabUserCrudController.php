<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * A fieldset and a tab require ROLE_ADMIN, but the fields inside them don't define any permission.
 *
 * @extends AbstractCrudController<User>
 */
class RestrictedTabUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('Profile'),
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            FormField::addFieldset('Address')->setPermission('ROLE_ADMIN'),
            TextField::new('address.city'),

            FormField::addTab('Private data')->setPermission('ROLE_ADMIN'),
            EmailField::new('email'),
        ];
    }
}
