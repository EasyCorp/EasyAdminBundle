<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * Tests disableUndisplayedActions(): 'new' is displayed on no page but has an
 * explicit permission.
 *
 * @extends AbstractCrudController<Category>
 */
class UndisplayedActionsWithPermissionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('slug'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disableUndisplayedActions()
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->setPermission(Action::NEW, 'PUBLIC_ACCESS')
        ;
    }
}
