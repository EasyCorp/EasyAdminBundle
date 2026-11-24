<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * The first tab requires ROLE_ADMIN, but the fields inside it don't define any permission.
 *
 * @extends AbstractCrudController<Category>
 */
class RestrictedFirstTabCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('URL')->setPermission('ROLE_ADMIN'),
            TextField::new('slug'),

            FormField::addTab('General'),
            TextField::new('name'),
        ];
    }
}
