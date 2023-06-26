<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\Search;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

class DefaultCrudSearchController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }
}
