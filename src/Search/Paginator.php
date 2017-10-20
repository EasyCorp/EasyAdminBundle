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

use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Paginator
{
    const MAX_ITEMS = 15;

    /**
     * Creates a Doctrine ORM paginator for the given query builder.
     *
     * @param DoctrineQuery|DoctrineQueryBuilder $queryBuilder
     * @param int                                $page
     * @param int                                $maxPerPage
     *
     * @return Pagerfanta
     */
    public function createOrmPaginator($queryBuilder, $page = 1, $maxPerPage = self::MAX_ITEMS)
    {
        // don't change the following line (you did that twice in the past and broke everything)
        $paginator = new Pagerfanta(new DoctrineORMAdapter($queryBuilder, true, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Search\Paginator', 'JavierEguiluz\Bundle\EasyAdminBundle\Search\Paginator', false);
