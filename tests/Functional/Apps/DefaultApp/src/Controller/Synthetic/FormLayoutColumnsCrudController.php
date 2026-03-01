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
 * CrudController for testing form columns layout.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutColumnsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // left column (6 columns = half width)
        yield FormField::addColumn(6, 'Left Column', 'fa fa-arrow-left');
        yield TextField::new('name');
        yield TextareaField::new('description');
        yield EmailField::new('email');
        yield TelephoneField::new('phone');

        // right column (6 columns = half width)
        yield FormField::addColumn(6, 'Right Column', 'fa fa-arrow-right');
        yield TextField::new('street');
        yield TextField::new('city');
        yield TextField::new('postalCode');
        yield CountryField::new('country');
        yield BooleanField::new('isActive');
        yield DateTimeField::new('createdAt');
        yield ArrayField::new('tags');
        yield IntegerField::new('priority');
    }
}
