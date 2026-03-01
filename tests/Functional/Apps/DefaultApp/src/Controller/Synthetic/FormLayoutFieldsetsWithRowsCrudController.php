<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * CrudController for testing fieldsets with row breaks inside.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutFieldsetsWithRowsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // fieldset 1: Basic Information with row breaks
        yield FormField::addFieldset('Basic Information', 'fa fa-info-circle');
        yield TextField::new('name');
        yield TextareaField::new('description');
        yield FormField::addRow();
        yield EmailField::new('email');
        yield TelephoneField::new('phone');

        // fieldset 2: Address with multiple row breaks
        yield FormField::addFieldset('Address', 'fa fa-map-marker')
            ->collapsible();
        yield TextField::new('street');
        yield FormField::addRow();
        yield TextField::new('city');
        yield TextField::new('postalCode');
        yield FormField::addRow();
        yield CountryField::new('country');

        // fieldset 3: Settings with row breaks
        yield FormField::addFieldset('Settings', 'fa fa-cog');
        yield BooleanField::new('isActive');
        yield IntegerField::new('priority');
        yield FormField::addRow();
        yield DateTimeField::new('createdAt');
        yield FormField::addRow();
        yield ArrayField::new('tags');
    }
}
