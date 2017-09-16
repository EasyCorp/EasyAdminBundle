<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Search;

use Pagerfanta\Pagerfanta;

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

    /**
     * @param array  $entityConfig
     * @param string $searchQuery
     * @param int    $page
     * @param int    $maxResults
     * @param string $sortField
     * @param string $sortDirection
     *
     * @return Pagerfanta
     */
    public function findByAllProperties(array $entityConfig, $searchQuery, $page = 1, $maxResults = self::MAX_RESULTS, $sortField = null, $sortDirection = null)
    {
        $queryBuilder = $this->queryBuilder->createSearchQueryBuilder($entityConfig, $searchQuery, $sortField, $sortDirection);

        return $this->paginator->createOrmPaginator($queryBuilder, $page, $maxResults);
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Search\Finder', 'JavierEguiluz\Bundle\EasyAdminBundle\Search\Finder', false);
