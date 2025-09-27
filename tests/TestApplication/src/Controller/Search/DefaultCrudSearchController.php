<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\Search;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

/**
 * @extends AbstractCrudController<BlogPost>
 */
class DefaultCrudSearchController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPaginatorPageSize(15);
    }
}
