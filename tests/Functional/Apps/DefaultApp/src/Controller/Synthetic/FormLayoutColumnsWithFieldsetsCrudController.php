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
 * CrudController for testing columns containing fieldsets.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutColumnsWithFieldsetsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // column 1: Multiple fieldsets
        yield FormField::addColumn(6);

        // fieldset: Basic Information
        yield FormField::addFieldset('Basic Information', 'fa fa-info-circle');
        yield TextField::new('name');
        yield TextareaField::new('description');

        // fieldset: Contact Information
        yield FormField::addFieldset('Contact Information', 'fa fa-phone');
        yield EmailField::new('email');
        yield TelephoneField::new('phone');

        // column 2: Fieldsets with collapsible states
        yield FormField::addColumn(6);

        // fieldset: Address (collapsible)
        yield FormField::addFieldset('Address', 'fa fa-map-marker')
            ->collapsible();
        yield TextField::new('street');
        yield TextField::new('city');
        yield TextField::new('postalCode');
        yield CountryField::new('country');

        // fieldset: Settings (collapsed by default)
        yield FormField::addFieldset('Settings', 'fa fa-cog')
            ->renderCollapsed();
        yield BooleanField::new('isActive');
        yield DateTimeField::new('createdAt');
        yield IntegerField::new('priority');
        yield ArrayField::new('tags');
    }
}
