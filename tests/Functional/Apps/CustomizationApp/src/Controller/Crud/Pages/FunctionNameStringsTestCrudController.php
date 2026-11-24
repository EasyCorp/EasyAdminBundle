<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pages;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\DemoEntity;

/**
 * CRUD controller for testing that the strings which match the name of a PHP
 * function (e.g. 'Time', 'Trim', 'reset') are not called as callables.
 */
class FunctionNameStringsTestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DemoEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Time')
            ->setPageTitle(Crud::PAGE_NEW, 'Time')
            ->setPageTitle(Crud::PAGE_EDIT, 'Trim')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Trim');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_DETAIL, Action::new('reset', 'Reset')->linkToUrl('reset'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
    }
}
