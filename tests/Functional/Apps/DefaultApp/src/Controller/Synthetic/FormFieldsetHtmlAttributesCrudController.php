<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * @extends AbstractCrudController<FormTestEntity>
 */
class FormFieldsetHtmlAttributesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FormTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield FormField::addFieldset('Fieldset With Html Attribute')
            ->setHtmlAttribute('data-foo', 'bar')
            ->setHtmlAttribute('class', 'custom-fieldset-class');
        yield TextField::new('name');

        yield FormField::addFieldset('Fieldset With Form Type Options')
            ->setFormTypeOptions(['attr' => ['data-baz' => 'qux']]);
        yield TextField::new('description');
    }
}
