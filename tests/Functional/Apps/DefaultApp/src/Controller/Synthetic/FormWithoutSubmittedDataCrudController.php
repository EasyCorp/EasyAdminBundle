<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Its form has CSRF protection disabled and only one editable field, a switch,
 * so unchecking it makes the browser send no data under the form name (see #7819).
 *
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormWithoutSubmittedDataCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setFormOptions(['csrf_protection' => false]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')->setDisabled();
        yield BooleanField::new('isActive');
    }
}
