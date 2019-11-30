<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

interface EntityRepositoryInterface
{
    public function createQueryBuilder(SearchDto $searchDto, EntityDto $entityDto): QueryBuilder;
}
