<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class PaginatorFactory
{
    private $adminContextProvider;
    private $entityPaginator;

    public function __construct(AdminContextProvider $adminContextProvider, EntityPaginator $entityPaginator)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->entityPaginator = $entityPaginator;
    }

    public function create(QueryBuilder $queryBuilder): EntityPaginator
    {
        $adminContext = $this->adminContextProvider->getContext();
        $paginatorDto = $adminContext->getCrud()->getPaginator();
        $paginatorDto->setPageNumber($adminContext->getRequest()->query->get('page', 1));

        return $this->entityPaginator->paginate($paginatorDto, $queryBuilder);
    }
}
