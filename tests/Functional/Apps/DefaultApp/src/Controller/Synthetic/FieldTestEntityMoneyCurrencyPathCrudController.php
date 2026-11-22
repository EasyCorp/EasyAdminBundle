<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;

/**
 * @extends AbstractCrudController<FieldTestEntity>
 */
class FieldTestEntityMoneyCurrencyPathCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FieldTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield MoneyField::new('moneyField')->setCurrencyPropertyPath('currencyField')->setStoredAsCents(true);
    }
}
