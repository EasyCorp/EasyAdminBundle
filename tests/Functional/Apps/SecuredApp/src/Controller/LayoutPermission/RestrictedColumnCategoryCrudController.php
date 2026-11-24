<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * The second column requires ROLE_ADMIN, but the fields and the fieldset inside it don't define any permission.
 *
 * @extends AbstractCrudController<Category>
 */
class RestrictedColumnCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(),
            TextField::new('name'),

            FormField::addColumn()->setPermission('ROLE_ADMIN'),
            TextField::new('slug'),
            FormField::addFieldset('Status'),
            BooleanField::new('active'),

            FormField::addColumn(),
            BooleanField::new('activeDisabled'),
        ];
    }
}
