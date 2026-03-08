<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;

/**
 * @extends AbstractCrudController<Category>
 */
class ErrorFieldDoesNotBelongToAnyTabCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // these fields are not inside any tab on purpose to trigger an error
            TextField::new('name'),
            TextField::new('slug'),
            FormField::addTab('Tab 1'),
            TextField::new('id'),
        ];
    }
}
