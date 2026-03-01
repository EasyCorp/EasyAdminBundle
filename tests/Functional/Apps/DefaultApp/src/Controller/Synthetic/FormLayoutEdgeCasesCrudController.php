<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * CrudController for testing edge cases and unusual configurations.
 * Each field must only be used once to avoid conflicts.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutEdgeCasesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        // tab 1: Empty fieldset edge case
        yield FormField::addTab('Empty Tab', 'fa fa-ban');
        yield FormField::addFieldset('Empty Fieldset');
        // no fields in this fieldset
        yield FormField::addFieldset('Single Field Fieldset');
        yield TextField::new('name');

        // tab 2: Many fields to test performance/rendering
        yield FormField::addTab('Many Fields', 'fa fa-list');
        yield EmailField::new('email');
        yield TelephoneField::new('phone');
        yield TextareaField::new('description');
        yield TextField::new('street');
        yield TextField::new('city');
        yield TextField::new('postalCode');
        yield CountryField::new('country');
        yield BooleanField::new('isActive');
        yield DateTimeField::new('createdAt');
        yield IntegerField::new('priority');
        yield IntegerField::new('priceInCents');
        yield NumberField::new('score');
        yield ChoiceField::new('status')
            ->setChoices([
                'Draft' => 'draft',
                'Published' => 'published',
                'Archived' => 'archived',
            ]);

        // tab 3: Multiple consecutive row breaks (edge case)
        yield FormField::addTab('Multiple Rows', 'fa fa-bars');
        yield ArrayField::new('tags');
        yield FormField::addRow();
        yield FormField::addRow();
        // additional empty row breaks demonstrate edge case handling
    }
}
