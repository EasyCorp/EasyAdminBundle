<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntitySearchEvent;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityRepository implements EntityRepositoryInterface
{
    private $adminContextProvider;
    private $doctrine;
    private $entityFactory;
    private $formFactory;
    private $eventDispatcher;

    public function __construct(AdminContextProvider $adminContextProvider, ManagerRegistry $doctrine, EntityFactory $entityFactory, FormFactory $formFactory, EventDispatcherInterface $eventDispatcher)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->doctrine = $doctrine;
        $this->entityFactory = $entityFactory;
        $this->formFactory = $formFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $entityManager = $this->doctrine->getManagerForClass($entityDto->getFqcn());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('entity')
            ->from($entityDto->getFqcn(), 'entity')
        ;

        if (!empty($searchDto->getQuery())) {
            $this->addSearchClause($queryBuilder, $searchDto, $entityDto);
        }

        if (!empty($searchDto->getAppliedFilters())) {
            $this->addFilterClause($queryBuilder, $searchDto, $entityDto, $filters, $fields);
        }

        $this->addOrderClause($queryBuilder, $searchDto, $entityDto);

        return $queryBuilder;
    }

    private function addSearchClause(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto): void
    {
        $query = $searchDto->getQuery();
        $lowercaseQuery = mb_strtolower($query);
        $isNumericQuery = is_numeric($query);
        $isSmallIntegerQuery = ctype_digit($query) && $query >= -32768 && $query <= 32767;
        $isIntegerQuery = ctype_digit($query) && $query >= -2147483648 && $query <= 2147483647;
        $isUuidQuery = Uuid::isValid($query);
        $isUlidQuery = Ulid::isValid($query);

        $dqlParameters = [
            // adding '0' turns the string into a numeric value
            'numeric_query' => is_numeric($query) ? 0 + $query : $query,
            'uuid_query' => $query,
            'text_query' => '%'.$lowercaseQuery.'%',
            'words_query' => explode(' ', $lowercaseQuery),
        ];

        $entitiesAlreadyJoined = [];
        $configuredSearchableProperties = $searchDto->getSearchableProperties();
        $searchableProperties = empty($configuredSearchableProperties) ? $entityDto->getAllPropertyNames() : $configuredSearchableProperties;
        foreach ($searchableProperties as $propertyName) {
            if ($entityDto->isAssociation($propertyName)) {
                // support arbitrarily nested associations (e.g. foo.bar.baz.qux)
                $associatedProperties = explode('.', $propertyName);
                $numAssociatedProperties = \count($associatedProperties);

                if (1 === $numAssociatedProperties) {
                    throw new \InvalidArgumentException(sprintf('The "%s" property included in the setSearchFields() method is not a valid search field. When using associated properties in search, you must also define the exact field used in the search (e.g. \'%s.id\', \'%s.name\', etc.)', $propertyName, $propertyName, $propertyName));
                }

                $originalPropertyName = $associatedProperties[0];
                $originalPropertyMetadata = $entityDto->getPropertyMetadata($originalPropertyName);
                $associatedEntityDto = $this->entityFactory->create($originalPropertyMetadata->get('targetEntity'));

                for ($i = 0; $i < $numAssociatedProperties - 1; ++$i) {
                    $associatedEntityName = $associatedProperties[$i];
                    $associatedEntityAlias = Escaper::escapeDqlAlias($associatedEntityName);
                    $associatedPropertyName = $associatedProperties[$i + 1];

                    if (!\in_array($associatedEntityName, $entitiesAlreadyJoined, true)) {
                        $parentEntityName = 0 === $i ? 'entity' : $associatedProperties[$i - 1];
                        $queryBuilder->leftJoin($parentEntityName.'.'.$associatedEntityName, $associatedEntityAlias);
                        $entitiesAlreadyJoined[] = $associatedEntityName;
                    }

                    if ($i < $numAssociatedProperties - 2) {
                        $propertyMetadata = $associatedEntityDto->getPropertyMetadata($associatedPropertyName);
                        $targetEntity = $propertyMetadata->get('targetEntity');
                        $associatedEntityDto = $this->entityFactory->create($targetEntity);
                    }
                }

                $entityName = $associatedEntityAlias;
                $propertyName = $associatedPropertyName;
                $propertyDataType = $associatedEntityDto->getPropertyDataType($propertyName);
            } else {
                $entityName = 'entity';
                $propertyDataType = $entityDto->getPropertyDataType($propertyName);
            }

            $isSmallIntegerProperty = 'smallint' === $propertyDataType;
            $isIntegerProperty = 'integer' === $propertyDataType;
            $isNumericProperty = \in_array($propertyDataType, ['number', 'bigint', 'decimal', 'float']);
            // 'citext' is a PostgreSQL extension (https://github.com/EasyCorp/EasyAdminBundle/issues/2556)
            $isTextProperty = \in_array($propertyDataType, ['string', 'text', 'citext', 'array', 'simple_array']);
            $isGuidProperty = \in_array($propertyDataType, ['guid', 'uuid']);
            $isUlidProperty = 'ulid' === $propertyDataType;

            // this complex condition is needed to avoid issues on PostgreSQL databases
            if (
                ($isSmallIntegerProperty && $isSmallIntegerQuery) ||
                ($isIntegerProperty && $isIntegerQuery) ||
                ($isNumericProperty && $isNumericQuery)
            ) {
                $queryBuilder->orWhere(sprintf('%s.%s = :query_for_numbers', $entityName, $propertyName))
                    ->setParameter('query_for_numbers', $dqlParameters['numeric_query']);
            } elseif ($isGuidProperty && $isUuidQuery) {
                $queryBuilder->orWhere(sprintf('%s.%s = :query_for_uuids', $entityName, $propertyName))
                    ->setParameter('query_for_uuids', $dqlParameters['uuid_query'], 'uuid' === $propertyDataType ? 'uuid' : null);
            } elseif ($isUlidProperty && $isUlidQuery) {
                $queryBuilder->orWhere(sprintf('%s.%s = :query_for_uuids', $entityName, $propertyName))
                    ->setParameter('query_for_uuids', $dqlParameters['uuid_query'], 'ulid');
            } elseif ($isTextProperty) {
                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) LIKE :query_for_text', $entityName, $propertyName))
                    ->setParameter('query_for_text', $dqlParameters['text_query']);
                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) IN (:query_as_words)', $entityName, $propertyName))
                    ->setParameter('query_as_words', $dqlParameters['words_query']);
            }
        }

        $this->eventDispatcher->dispatch(new AfterEntitySearchEvent($queryBuilder, $searchDto, $entityDto));
    }

    private function addOrderClause(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto): void
    {
        foreach ($searchDto->getSort() as $sortProperty => $sortOrder) {
            $aliases = $queryBuilder->getAllAliases();
            $sortFieldIsDoctrineAssociation = $entityDto->isAssociation($sortProperty);

            if ($sortFieldIsDoctrineAssociation) {
                $sortFieldParts = explode('.', $sortProperty, 2);
                // check if join has been added once before.
                if (!\in_array($sortFieldParts[0], $aliases, true)) {
                    $queryBuilder->leftJoin('entity.'.$sortFieldParts[0], $sortFieldParts[0]);
                }

                if (1 === \count($sortFieldParts)) {
                    $queryBuilder->addOrderBy('entity.'.$sortProperty, $sortOrder);
                } else {
                    $queryBuilder->addOrderBy($sortProperty, $sortOrder);
                }
            } else {
                $queryBuilder->addOrderBy('entity.'.$sortProperty, $sortOrder);
            }
        }
    }

    private function addFilterClause(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto, FilterCollection $configuredFilters, FieldCollection $fields): void
    {
        $filtersForm = $this->formFactory->createFiltersForm($configuredFilters, $this->adminContextProvider->getContext()->getRequest());
        if (!$filtersForm->isSubmitted()) {
            return;
        }

        $appliedFilters = $searchDto->getAppliedFilters();
        $i = 0;
        foreach ($filtersForm as $filterForm) {
            $propertyName = $filterForm->getName();

            $filter = $configuredFilters->get($propertyName);
            // this filter is not defined or not applied
            if (null === $filter || !isset($appliedFilters[$propertyName])) {
                continue;
            }

            // if the form filter is not valid then we should not apply the filter
            if (!$filterForm->isValid()) {
                continue;
            }

            $submittedData = $filterForm->getData();
            if (!\is_array($submittedData)) {
                $submittedData = [
                    'comparison' => ComparisonType::EQ,
                    'value' => $submittedData,
                ];
            }

            $filterDataDto = FilterDataDto::new($i, $filter, current($queryBuilder->getRootAliases()), $submittedData);
            $filter->apply($queryBuilder, $filterDataDto, $fields->getByProperty($propertyName), $entityDto);

            ++$i;
        }
    }
}
