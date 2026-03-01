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
 * CrudController for testing tabs with multi-column layouts.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutTabsWithColumnsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // tab 1: 2-column layout
        yield FormField::addTab('Basic Information', 'fa fa-user');

        yield FormField::addColumn(6);
        yield TextField::new('name');
        yield EmailField::new('email');

        yield FormField::addColumn(6);
        yield TextareaField::new('description');
        yield TelephoneField::new('phone');

        // tab 2: 3-column layout
        yield FormField::addTab('Address', 'fa fa-map-marker');

        yield FormField::addColumn(4);
        yield TextField::new('street');

        yield FormField::addColumn(4);
        yield TextField::new('city');

        yield FormField::addColumn(4);
        yield TextField::new('postalCode');
        yield CountryField::new('country');

        // tab 3: Single column (default)
        yield FormField::addTab('Settings', 'fa fa-cog');

        yield BooleanField::new('isActive');
        yield DateTimeField::new('createdAt');
        yield IntegerField::new('priority');
        yield ArrayField::new('tags');
    }
}
