<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * CrudController for testing validation error handling with complex layouts.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormLayoutValidationErrorsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // tab 1: "Basic Information" (fa-user)
        yield FormField::addTab('Basic Information', 'fa fa-user');
        yield TextField::new('name')->setRequired(true);
        yield EmailField::new('email')->setRequired(true);
        yield TextareaField::new('description');

        // tab 2: "Address" (fa-map-marker)
        yield FormField::addTab('Address', 'fa fa-map-marker');

        // column 1 (width: 6)
        yield FormField::addColumn(6);
        yield TextField::new('street')->setRequired(true);
        yield TextField::new('city')->setRequired(true);

        // column 2 (width: 6)
        yield FormField::addColumn(6);
        yield TextField::new('postalCode');
        yield CountryField::new('country')->setRequired(true);

        // tab 3: "Settings" (fa-cog)
        yield FormField::addTab('Settings', 'fa fa-cog');

        // fieldset: "Status Settings" (collapsed by default)
        yield FormField::addFieldset('Status Settings')
            ->renderCollapsed();
        yield BooleanField::new('isActive');
        yield IntegerField::new('priority')
            ->setRequired(true)
            ->setFormTypeOption('constraints', [new NotNull()]);

        // fieldset: "Metadata"
        yield FormField::addFieldset('Metadata');
        yield DateTimeField::new('createdAt');
        yield ChoiceField::new('status')
            ->setChoices([
                'Draft' => 'draft',
                'Published' => 'published',
                'Archived' => 'archived',
            ]);
    }
}
