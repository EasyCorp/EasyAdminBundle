<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Search;

use Pagerfanta\Pagerfanta;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Finder
{
    private const MAX_RESULTS = 15;

    private $queryBuilder;
    private $paginator;

    public function __construct(QueryBuilder $queryBuilder, Paginator $paginator)
    {
        $this->queryBuilder = $queryBuilder;
        $this->paginator = $paginator;
    }

    public function findByAllProperties(array $entityConfig, string $searchQuery, int $page = 1, int $maxResults = self::MAX_RESULTS, string $sortField = null, string $sortDirection = null, string $dqlFilter = null): Pagerfanta
    {
        $queryBuilder = $this->queryBuilder->createSearchQueryBuilder($entityConfig, $searchQuery, $sortField, $sortDirection, $dqlFilter);

        return $this->paginator->createOrmPaginator($queryBuilder, $page, $maxResults);
    }
}
