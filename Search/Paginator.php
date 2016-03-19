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

use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Paginator
{
    /**
     * Creates a Doctrine ORM paginator for the given query builder.
     *
     * @param DoctrineQueryBuilder $queryBuilder
     * @param int                  $page
     * @param int                  $maxPerPage
     *
     * @return Pagerfanta
     */
    public function createOrmPaginator(DoctrineQueryBuilder $queryBuilder, $page, $maxPerPage)
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($queryBuilder, true, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
