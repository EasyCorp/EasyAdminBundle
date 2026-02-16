<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * CrudController for testing field value formatting.
 * Tests that formatValue() receives the original value, not the one modified with other options.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormFieldValueSyntheticCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // these fields format the original value with some options (e.g. max length)
        // and then use the formatValue() method to test that this method receives the
        // original field value, not the one modified with the other options
        return [
            // 1. TextField with maxLength - verify full string received, not truncated
            TextField::new('name')->setMaxLength(2)->formatValue(static fn ($value, $entity) => $value),

            // 2. DateTimeField with format - verify DateTime object received, not formatted string
            DateTimeField::new('createdAt')->setFormat('long', 'long')
                ->formatValue(static fn (/** @var \DateTimeInterface|null $value */ $value, $entity) => $value ? date('YmdHis', $value->getTimestamp()) : 'NULL'),

            // 3. IntegerField with number formatting - verify raw integer received, not formatted string
            IntegerField::new('priority')->setNumberFormat('%05d')
                ->formatValue(static fn ($value, $entity) => "RAW:{$value}"),

            // 4. MoneyField with divisor (cents) - verify original cents value received, not divided dollar amount
            MoneyField::new('priceInCents', 'Price')->setCurrency('USD')->setStoredAsCents()
                ->formatValue(static fn ($value, $entity) => "CENTS:{$value}"),

            // 5. NumberField with decimals/separators - verify full precision float received
            NumberField::new('score')->setNumDecimals(2)
                ->formatValue(static fn ($value, $entity) => "FULL:{$value}"),

            // 6. ChoiceField with choices - verify raw choice value received, not label
            ChoiceField::new('status')->setChoices(['Active' => 'active', 'Inactive' => 'inactive'])
                ->formatValue(static fn ($value, $entity) => "CHOICE:{$value}"),

            // 7. TextField for null handling - verify null is preserved
            TextField::new('description')
                ->formatValue(static fn ($value, $entity) => null === $value ? 'IS_NULL' : "NOT_NULL:{$value}"),

            // 8. IdField - verify raw ID value received
            IdField::new('id')
                ->formatValue(static fn ($value, $entity) => "ID:{$value}"),
        ];
    }
}
