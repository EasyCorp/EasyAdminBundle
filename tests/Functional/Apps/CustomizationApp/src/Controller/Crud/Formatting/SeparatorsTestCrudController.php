<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\BlogPost;

/**
 * CRUD controller for testing Crud::setThousandsSeparator() and setDecimalSeparator().
 */
class SeparatorsTestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setThousandsSeparator('.')
            ->setDecimalSeparator(',');
    }

    public function configureFields(string $pageName): iterable
    {
        yield NumberField::new('rating');
        yield NumberField::new('views');
    }
}
