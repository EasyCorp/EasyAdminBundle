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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class QueryBuilder
{
    /** @var Registry */
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Creates the query builder used to get all the records displayed by the
     * "list" view.
     *
     * @param array  $entityConfig
     * @param string $sortDirection
     * @param string $sortField
     *
     * @return DoctrineQueryBuilder
     */
    public function createListQueryBuilder(array $entityConfig, $sortField, $sortDirection)
    {
        $em = $this->doctrine->getManagerForClass($entityConfig['class']);
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity')
            ->from($entityConfig['class'], 'entity')
        ;

        if (null !== $sortField) {
            $queryBuilder->orderBy('entity.'.$sortField, $sortDirection);
        }

        return $queryBuilder;
    }

    /**
     * Creates the query builder used to get the results of the search query
     * performed by the user in the "search" view.
     *
     * @param array $entityConfig
     * @param $searchQuery
     * @param $sortField
     * @param $sortDirection
     *
     * @return DoctrineQueryBuilder
     */
    public function createSearchQueryBuilder(array $entityConfig, $searchQuery, $sortField, $sortDirection)
    {
        $em = $this->doctrine->getManagerForClass($entityConfig['class']);
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity')
            ->from($entityConfig['class'], 'entity')
        ;

        $databaseIsPostgreSql = $this->isPostgreSqlPlatform($entityConfig['class']);

        $queryParameters = array();
        foreach ($entityConfig['search']['fields'] as $name => $metadata) {
            $isNumericField = in_array($metadata['dataType'], array('integer', 'number', 'smallint', 'bigint', 'decimal', 'float'));
            $isTextField = in_array($metadata['dataType'], array('string', 'text', 'guid'));

            if (is_numeric($searchQuery) && $isNumericField) {
                $queryBuilder->orWhere(sprintf('entity.%s = :exact_query', $name));
                // adding '0' turns the string into a numeric value
                $queryParameters['exact_query'] = 0 + $searchQuery;
            } elseif ($isTextField) {
                $queryBuilder->orWhere(sprintf('entity.%s LIKE :fuzzy_query', $name));
                $queryParameters['fuzzy_query'] = '%'.$searchQuery.'%';
            } else {
                // PostgreSQL doesn't allow to search string values in non-string columns
                if ($databaseIsPostgreSql) {
                    continue;
                }

                $queryBuilder->orWhere(sprintf('entity.%s IN (:words_query)', $name));
                $queryParameters['words_query'] = explode(' ', $searchQuery);
            }
        }

        if (!empty($queryParameters)) {
            $queryBuilder->setParameters($queryParameters);
        }

        if (null !== $sortField) {
            $queryBuilder->orderBy('entity.'.$sortField, $sortDirection);
        }

        return $queryBuilder;
    }

    /**
     * Returns true if the data of the given entity are stored in a database
     * of type PostgreSQL.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    private function isPostgreSqlPlatform($entityClass)
    {
        $em = $this->doctrine->getManagerForClass($entityClass);

        return $em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }
}
