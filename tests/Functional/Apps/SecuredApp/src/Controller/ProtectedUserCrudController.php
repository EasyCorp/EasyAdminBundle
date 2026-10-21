<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * Used by AssociationLinkPermissionTest: the DETAIL action requires a role, so links
 * pointing to this controller must only be rendered for users holding it.
 *
 * @extends AbstractCrudController<User>
 */
class ProtectedUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->setPermission(Action::DETAIL, 'ROLE_ADMIN');
    }
}
