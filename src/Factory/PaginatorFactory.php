<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class PaginatorFactory
{
    public function __construct(
        private AdminContextProviderInterface $adminContextProvider,
        private EntityPaginatorInterface $entityPaginator,
    ) {
    }

    /**
     * When the fields displayed in the page are passed, the to-one associations shown in
     * the page are loaded with a single query per associated entity instead of one query
     * per row (see EntityPaginator).
     */
    public function create(QueryBuilder $queryBuilder, ?FieldCollection $fields = null): EntityPaginatorInterface
    {
        $adminContext = $this->adminContextProvider->getContext();
        $paginatorDto = $adminContext->getCrud()->getPaginator();
        $paginatorDto->setPageNumber((int) $adminContext->getRequest()->query->get('page', '1'));

        // the paginator DTO is shared by all the paginators of the request, so the
        // associations of a previous call must be overwritten
        $paginatorDto->setEagerFetchedAssociations(null === $fields ? [] : $this->findEagerFetchedAssociations($fields, $adminContext->getEntity(), $adminContext->getCrud()->getCurrentPage() ?? Crud::PAGE_INDEX));

        return $this->entityPaginator->paginate($paginatorDto, $queryBuilder);
    }

    /**
     * The decision is based on the Doctrine metadata of the property and not on the field
     * type because any field (e.g. TextField) displaying a to-one association property loads
     * the associated entity when rendering it.
     *
     * @return array<class-string, list<string>>
     */
    private function findEagerFetchedAssociations(FieldCollection $fields, EntityDto $entityDto, string $pageName): array
    {
        $classMetadata = $entityDto->getClassMetadata();

        $propertyNames = [];
        foreach ($fields as $field) {
            if (!$field->isDisplayedOn($pageName)) {
                continue;
            }

            // for nested properties (e.g. 'author.company') only the first association can be loaded
            // eagerly, because Doctrine hydrates the associated entities using their own fetch mode
            $propertyName = explode('.', $field->getProperty(), 2)[0];
            if (!$classMetadata->hasAssociation($propertyName) || !$classMetadata->isSingleValuedAssociation($propertyName)) {
                continue;
            }

            $propertyNames[$propertyName] = $propertyName;
        }

        if ([] === $propertyNames) {
            return [];
        }

        // Doctrine looks up the fetch mode using the class name of each hydrated entity, so
        // it must be defined for the root entity class and for each of its subclasses
        $eagerFetchedAssociations = [];
        foreach ([$classMetadata->getName(), ...$classMetadata->subClasses] as $entityFqcn) {
            $eagerFetchedAssociations[$entityFqcn] = array_values($propertyNames);
        }

        return $eagerFetchedAssociations;
    }
}
