<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Templates;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\Category;

/**
 * CRUD controller for testing the overriding of the individual templates
 * that compose the detail page (see 'crud/detail/*' names in TemplateRegistry).
 */
class OverrideDetailPartialsTestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplate('crud/detail/field_group', 'admin/detail/field_group.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Category Info');
        yield IdField::new('id');
        yield TextField::new('name');
    }
}
