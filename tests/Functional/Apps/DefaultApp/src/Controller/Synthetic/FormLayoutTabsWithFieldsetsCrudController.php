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
 * CrudController for testing tabs containing fieldsets.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutTabsWithFieldsetsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // tab 1: Multiple fieldsets
        yield FormField::addTab('User Details', 'fa fa-user');

        // fieldset: Basic Info
        yield FormField::addFieldset('Basic Info', 'fa fa-info-circle');
        yield TextField::new('name');
        yield TextareaField::new('description');

        // fieldset: Contact
        yield FormField::addFieldset('Contact', 'fa fa-phone');
        yield EmailField::new('email');
        yield TelephoneField::new('phone');

        // tab 2: Fieldsets with collapsible/collapsed states
        yield FormField::addTab('Address & Settings', 'fa fa-cog');

        // fieldset: Address (collapsible)
        yield FormField::addFieldset('Address', 'fa fa-map-marker')
            ->collapsible();
        yield TextField::new('street');
        yield TextField::new('city');
        yield TextField::new('postalCode');
        yield CountryField::new('country');

        // fieldset: Advanced Settings (collapsed by default)
        yield FormField::addFieldset('Advanced Settings', 'fa fa-wrench')
            ->renderCollapsed();
        yield BooleanField::new('isActive');
        yield DateTimeField::new('createdAt');
        yield IntegerField::new('priority');
        yield ArrayField::new('tags');
    }
}
