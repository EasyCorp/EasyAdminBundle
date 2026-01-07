<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

final class AfterEntitySearchEvent
{
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly SearchDto $searchDto,
        private readonly EntityDto $entityDto,
    ) {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getSearchDto(): SearchDto
    {
        return $this->searchDto;
    }

    public function getEntityDto(): EntityDto
    {
        return $this->entityDto;
    }
}
