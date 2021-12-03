<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

final class AfterEntitySearchEvent
{
    private $queryBuilder;
    private $searchDto;
    private $entityDto;

    public function __construct(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto)
    {
        $this->queryBuilder = $queryBuilder;
        $this->searchDto = $searchDto;
        $this->entityDto = $entityDto;
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
