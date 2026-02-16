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
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutFieldsetsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideWhenCreating();

        // fieldset 1: Basic Information
        yield FormField::addFieldset('Basic Information', 'fa fa-info-circle')
            ->addCssClass('fieldset-basic');
        yield TextField::new('name');
        yield TextareaField::new('description');

        // fieldset 2: Contact Information
        yield FormField::addFieldset('Contact Information', 'fa fa-phone')
            ->addCssClass('fieldset-contact');
        yield EmailField::new('email');
        yield TelephoneField::new('phone');

        // fieldset 3: Address (collapsible)
        yield FormField::addFieldset('Address', 'fa fa-map-marker')
            ->addCssClass('fieldset-address')
            ->collapsible();
        yield TextField::new('street');
        yield TextField::new('city');
        yield TextField::new('postalCode');
        yield CountryField::new('country');

        // fieldset 4: Settings (collapsed by default)
        yield FormField::addFieldset('Settings', 'fa fa-cog')
            ->addCssClass('fieldset-settings')
            ->renderCollapsed();
        yield BooleanField::new('isActive');
        yield DateTimeField::new('createdAt');
        yield ArrayField::new('tags');
        yield IntegerField::new('priority');
    }
}
