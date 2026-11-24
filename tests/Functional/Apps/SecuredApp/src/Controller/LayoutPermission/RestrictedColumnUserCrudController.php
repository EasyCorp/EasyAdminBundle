<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * The second column requires ROLE_ADMIN, but the fields and the fieldset inside it don't define any permission.
 *
 * @extends AbstractCrudController<User>
 */
class RestrictedColumnUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(),
            TextField::new('name'),

            FormField::addColumn()->setPermission('ROLE_ADMIN'),
            EmailField::new('email'),
            FormField::addFieldset('Address'),
            TextField::new('address.city'),

            FormField::addColumn(),
            IntegerField::new('address.street'),
        ];
    }
}
