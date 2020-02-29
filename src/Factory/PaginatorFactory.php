<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;

final class PaginatorFactory
{
    private $applicationContextProvider;
    private $entityPaginator;

    public function __construct(ApplicationContextProvider $applicationContextProvider, EntityPaginator $entityPaginator)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->entityPaginator = $entityPaginator;
    }

    public function create(QueryBuilder $queryBuilder): EntityPaginator
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $paginatorDto = $applicationContext->getCrud()->getPaginator()->with([
            'pageNumber' => $applicationContext->getRequest()->query->get('page', 1),
        ]);

        return $this->entityPaginator->paginate($paginatorDto, $queryBuilder);
    }
}
