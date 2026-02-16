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
 * CrudController for testing complete integration of all layout features
 * (Tabs + Columns + Fieldsets + Rows).
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutCompleteIntegrationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        // tab 1: "User Information" (fa-user)
        yield FormField::addTab('User Information', 'fa fa-user');

        // column 1 (width: 6)
        yield FormField::addColumn(6);
        // fieldset: "Personal Details" (fa-id-card)
        yield FormField::addFieldset('Personal Details', 'fa fa-id-card');
        yield TextField::new('name');
        yield TextareaField::new('description');
        yield FormField::addRow();
        yield EmailField::new('email');
        yield TelephoneField::new('phone');

        // column 2 (width: 6)
        yield FormField::addColumn(6);
        // fieldset: "Address Information" (fa-map-marker, collapsible)
        yield FormField::addFieldset('Address Information', 'fa fa-map-marker')
            ->collapsible();
        yield TextField::new('street');
        yield TextField::new('city');
        yield FormField::addRow();
        yield TextField::new('postalCode');
        yield CountryField::new('country');

        // tab 2: "Settings & Metadata" (fa-cog)
        yield FormField::addTab('Settings & Metadata', 'fa fa-cog');

        // fieldset: "Status" (fa-toggle-on, collapsed by default)
        yield FormField::addFieldset('Status', 'fa fa-toggle-on')
            ->renderCollapsed();
        yield BooleanField::new('isActive');
        yield ChoiceField::new('status')
            ->setChoices([
                'Draft' => 'draft',
                'Published' => 'published',
                'Archived' => 'archived',
            ]);

        yield FormField::addRow();

        // fieldset: "Metadata" (fa-info-circle)
        yield FormField::addFieldset('Metadata', 'fa fa-info-circle');
        yield DateTimeField::new('createdAt');
        yield IntegerField::new('priority');
        yield FormField::addRow();
        yield IntegerField::new('priceInCents');
        yield NumberField::new('score');
        yield ArrayField::new('tags');
    }
}
