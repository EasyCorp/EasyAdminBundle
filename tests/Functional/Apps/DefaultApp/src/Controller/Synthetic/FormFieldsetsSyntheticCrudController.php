<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * CrudController for testing fieldsets with fields both inside and outside fieldsets.
 * This tests the automatic fieldset creation and manual fieldset configuration.
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormFieldsetsSyntheticCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // this field is out of any fieldset on purpose
            IdField::new('id'),
            FormField::addFieldset('Fieldset 1')->setIcon('fa fa-cog')->addCssClass('bg-info'),
            TextField::new('name'),
            FormField::addFieldset('Fieldset 2')->setIcon('fa fa-user')->addCssClass('bg-warning'),
            TextareaField::new('description'),
            // this fieldset is added after all fields on purpose
            FormField::addFieldset('Fieldset 3')->setIcon('fa fa-file-alt')->addCssClass('bg-danger'),
        ];
    }
}
