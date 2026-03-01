<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pages;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\DemoEntity;

/**
 * CRUD controller for testing Crud::setHelp().
 */
class HelpMessagesTestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DemoEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setHelp('index', 'This is the index page help message')
            ->setHelp('new', 'This is the new page help message')
            ->setHelp('edit', 'This is the edit page help message')
            ->setHelp('detail', 'This is the detail page help message');
    }
}
