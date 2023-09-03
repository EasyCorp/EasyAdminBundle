<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface PaginatorFactoryInterface
{
    public function create(QueryBuilder $queryBuilder): EntityPaginatorInterface;
}
