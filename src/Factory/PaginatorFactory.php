<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Orm\ToOneAssociationLoader;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class PaginatorFactory
{
    public function __construct(
        private AdminContextProviderInterface $adminContextProvider,
        private EntityPaginatorInterface $entityPaginator,
        private ToOneAssociationLoader $toOneAssociationLoader,
    ) {
    }

    /**
     * When the fields displayed in the page are passed, the to-one associations shown in
     * the page are loaded with a single query per associated entity instead of one query
     * per row (see ToOneAssociationLoader).
     */
    public function create(QueryBuilder $queryBuilder, ?FieldCollection $fields = null): EntityPaginatorInterface
    {
        $adminContext = $this->adminContextProvider->getContext();
        $paginatorDto = $adminContext->getCrud()->getPaginator();
        $paginatorDto->setPageNumber((int) $adminContext->getRequest()->query->get('page', '1'));

        $paginator = $this->entityPaginator->paginate($paginatorDto, $queryBuilder);

        // the associations are loaded before creating the entity DTOs because their
        // security checks (e.g. voters) could load the associations of each row one by one
        if (null !== $fields && null !== $results = $paginator->getResults()) {
            $this->toOneAssociationLoader->load($results, $adminContext->getEntity()->getFqcn(), $fields, $adminContext->getCrud()->getCurrentPage() ?? Crud::PAGE_INDEX);
        }

        return $paginator;
    }
}
