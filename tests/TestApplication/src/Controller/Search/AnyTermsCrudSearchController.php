<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\Search;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

class AnyTermsCrudSearchController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setSearchFields(['id', 'author.email', 'publisher.email', 'author.blogPosts.publisher.email'])
            ->setSearchMode(SearchMode::ANY_TERMS);
    }
}
