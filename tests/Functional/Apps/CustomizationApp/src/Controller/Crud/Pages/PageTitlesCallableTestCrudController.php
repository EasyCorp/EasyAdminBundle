<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pages;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\DemoEntity;

/**
 * CRUD controller for testing callable page titles.
 */
class PageTitlesCallableTestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DemoEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', static fn () => 'Dynamic Index Title')
            ->setPageTitle('new', static fn () => 'Dynamic New Title');
    }
}
