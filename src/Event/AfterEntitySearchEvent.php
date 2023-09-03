<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDtoInterface;

final class AfterEntitySearchEvent
{
    private QueryBuilder $queryBuilder;
    private SearchDto $searchDto;
    private EntityDto $entityDto;

    public function __construct(QueryBuilder $queryBuilder, SearchDtoInterface $searchDto, EntityDtoInterface $entityDto)
    {
        $this->queryBuilder = $queryBuilder;
        $this->searchDto = $searchDto;
        $this->entityDto = $entityDto;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getSearchDto(): SearchDtoInterface
    {
        return $this->searchDto;
    }

    public function getEntityDto(): EntityDtoInterface
    {
        return $this->entityDto;
    }
}
