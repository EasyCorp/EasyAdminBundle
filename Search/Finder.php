<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Search;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Finder
{
    const MAX_RESULTS = 15;

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var Paginator */
    private $paginator;

    public function __construct(QueryBuilder $queryBuilder, Paginator $paginator)
    {
        $this->queryBuilder = $queryBuilder;
        $this->paginator = $paginator;
    }

    public function findByAllProperties(array $entityConfig, $searchQuery, $page = 1, $maxResults = self::MAX_RESULTS, $sortField = null, $sortDirection = null)
    {
        $queryBuilder = $this->queryBuilder->createSearchQueryBuilder($entityConfig, $searchQuery, $sortField, $sortDirection);

        return $this->paginator->createOrmPaginator($queryBuilder, $page, $maxResults);
    }
}
