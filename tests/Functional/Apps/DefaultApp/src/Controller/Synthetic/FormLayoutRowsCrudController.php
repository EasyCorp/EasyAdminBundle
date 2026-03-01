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
 * CrudController for testing form rows layout (using addRow to create visual row breaks).
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutRowsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // column layout with row breaks
        yield FormField::addColumn(6);

        // first group of fields
        yield TextField::new('name')->setColumns(6);
        yield TextareaField::new('description')->setColumns(6);

        // row break
        yield FormField::addRow();
        yield EmailField::new('email')->setColumns(6);
        yield TelephoneField::new('phone')->setColumns(6);

        // second column
        yield FormField::addColumn(6);
        yield TextField::new('street')->setColumns(12);

        // row break with responsive breakpoint
        yield FormField::addRow('md');
        yield TextField::new('city')->setColumns(6);
        yield TextField::new('postalCode')->setColumns(6);

        // another row break
        yield FormField::addRow();
        yield CountryField::new('country')->setColumns(12);
        yield BooleanField::new('isActive');
        yield DateTimeField::new('createdAt');
        yield ArrayField::new('tags');
        yield IntegerField::new('priority');
    }
}
