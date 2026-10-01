<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Association;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

/**
 * Same fields as the parent controller, but the associations are fetch joined in the
 * index query, so EasyAdmin must not run any additional query to load or count them.
 */
class FetchJoinedAssociationsCrudController extends BatchedAssociationsCrudController
{
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->leftJoin('entity.author', 'author')->addSelect('author')
            ->leftJoin('entity.publisher', 'publisher')->addSelect('publisher')
            ->leftJoin('entity.categories', 'categories')->addSelect('categories');
    }
}
