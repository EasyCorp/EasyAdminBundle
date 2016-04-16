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

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function findByAllProperties(array $entityConfig, $searchQuery, $maxResults = Finder::MAX_RESULTS, $sortField = null, $sortDirection = null)
    {
        $queryBuilder = $this->queryBuilder->createSearchQueryBuilder($entityConfig, $searchQuery, $sortField, $sortDirection);

        if (null !== $maxResults) {
            $queryBuilder->setMaxResults($maxResults);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
