<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Crud;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * Renders some fields as badges, with and without their own badge variant, to check
 * that the variant of a field takes precedence over the one configured in the CRUD.
 *
 * @extends AbstractCrudController<Category>
 */
class BadgeVariantCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            BooleanField::new('active')->renderAsSwitch(false),
            BooleanField::new('activeDisabled')->renderAsSwitch(false)->setBadgeVariant('outline'),
            ChoiceField::new('priority')->setChoices(['Low' => 1, 'High' => 2])->renderAsBadges(['1' => 'info', '2' => 'danger'])->setBadgeVariant('solid'),
        ];
    }
}
