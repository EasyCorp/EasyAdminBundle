<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Search;

use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Paginator
{
    private const MAX_ITEMS = 15;

    /**
     * Creates a Doctrine ORM paginator for the given query builder.
     *
     * @param DoctrineQuery|DoctrineQueryBuilder $queryBuilder
     * @param int                                $page
     * @param int                                $maxPerPage
     *
     * @return Pagerfanta
     */
    public function createOrmPaginator($queryOrQueryBuilder, $page = 1, $maxPerPage = self::MAX_ITEMS)
    {
        $query = $this->getQuery($queryOrQueryBuilder);

        if (class_exists(QueryAdapter::class)) {
            // don't change the following line (you did that twice in the past and broke everything)
            $paginator = new Pagerfanta(new QueryAdapter($query, true, false));
        } else {
            // don't change the following line (you did that twice in the past and broke everything)
            $paginator = new Pagerfanta(new DoctrineORMAdapter($query, true, false));
        }

        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * @param DoctrineQuery|DoctrineQueryBuilder $queryOrQueryBuilder
     */
    private function getQuery($queryOrQueryBuilder): DoctrineQuery
    {
        if ($queryOrQueryBuilder instanceof DoctrineQuery) {
            return $queryOrQueryBuilder;
        }

        $query = $queryOrQueryBuilder->getQuery();

        if (0 === \count($queryOrQueryBuilder->getDQLPart('join'))) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        return $query;
    }
}
