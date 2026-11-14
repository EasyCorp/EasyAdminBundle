<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ActionPermissions;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * Used by ActionPermissionsMergeTest: it restricts other actions than the
 * dashboard, so the permission of the dashboard must still apply.
 *
 * @extends AbstractCrudController<Category>
 */
class SetPermissionsCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
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
        return $actions->setPermissions([
            Action::NEW => 'ROLE_ADMIN',
            Action::EDIT => 'ROLE_ADMIN',
        ]);
    }
}
