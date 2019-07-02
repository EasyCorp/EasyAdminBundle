<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Search;

use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;

/**
 * @deprecated Use EasyCorp\Bundle\EasyAdminBundle\Search\QueryPaginator
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Paginator
{
    /**
     * Creates a Doctrine ORM paginator for the given query builder.
     *
     * @param DoctrineQuery|DoctrineQueryBuilder $queryBuilder
     * @param int                                $page
     * @param int                                $maxPerPage
     *
     * @return \Traversable
     */
    public function createOrmPaginator($queryBuilder, $page = 1, $maxPerPage = self::PAGE_SIZE)
    {
        @trigger_error(sprintf('The "%s" method is deprecated. Use "new %s()" instead to create the paginator.', __METHOD__, __CLASS__), E_USER_DEPRECATED);

        return new QueryPaginator($queryBuilder, $page, $maxPerPage);
    }
}
