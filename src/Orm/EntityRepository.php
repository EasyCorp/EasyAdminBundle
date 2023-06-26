<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\EntityManagerInterface;
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
    private AdminContextProvider $adminContextProvider;
    private ManagerRegistry $doctrine;
    private EntityFactory $entityFactory;
    private FormFactory $formFactory;
    private EventDispatcherInterface $eventDispatcher;

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
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($entityDto->getFqcn());
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('entity')
            ->from($entityDto->getFqcn(), 'entity')
        ;

        if ('' !== $searchDto->getQuery()) {
            $this->addSearchClause($queryBuilder, $searchDto, $entityDto);
        }

        $appliedFilters = $searchDto->getAppliedFilters();
        if (null !== $appliedFilters && 0 !== \count($appliedFilters)) {
            $this->addFilterClause($queryBuilder, $searchDto, $entityDto, $filters, $fields);
        }

        $this->addOrderClause($queryBuilder, $searchDto, $entityDto);

        return $queryBuilder;
    }

    private function addSearchClause(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto): void
    {
        $searchablePropertiesConfig = $this->getSearchablePropertiesConfig($queryBuilder, $searchDto, $entityDto);

        $queryTerms = $searchDto->getQueryTerms();
        $queryTermIndex = 0;
        foreach ($queryTerms as $queryTerm) {
            ++$queryTermIndex;

            $lowercaseQueryTerm = mb_strtolower($queryTerm);
            $isNumericQueryTerm = is_numeric($queryTerm);
            $isSmallIntegerQueryTerm = ctype_digit($queryTerm) && $queryTerm >= -32768 && $queryTerm <= 32767;
            $isIntegerQueryTerm = ctype_digit($queryTerm) && $queryTerm >= -2147483648 && $queryTerm <= 2147483647;
            $isUuidQueryTerm = Uuid::isValid($queryTerm);
            $isUlidQueryTerm = Ulid::isValid($queryTerm);

            $dqlParameters = [
                // adding '0' turns the string into a numeric value
                'numeric_query' => is_numeric($queryTerm) ? 0 + $queryTerm : $queryTerm,
                'uuid_query' => $queryTerm,
                'text_query' => '%'.$lowercaseQueryTerm.'%',
            ];

            foreach ($searchablePropertiesConfig as $propertyName => $propertyConfig) {
                $entityName = $propertyConfig['entity_name'];

                // this complex condition is needed to avoid issues on PostgreSQL databases
                if (
                    ($propertyConfig['is_small_integer'] && $isSmallIntegerQueryTerm)
                    || ($propertyConfig['is_integer'] && $isIntegerQueryTerm)
                    || ($propertyConfig['is_numeric'] && $isNumericQueryTerm)
                ) {
                    $parameterName = sprintf('query_for_numbers_%d', $queryTermIndex);
                    $queryBuilder->orWhere(sprintf('%s.%s = :%s', $entityName, $propertyName, $parameterName))
                        ->setParameter($parameterName, $dqlParameters['numeric_query']);
                } elseif ($propertyConfig['is_guid'] && $isUuidQueryTerm) {
                    $parameterName = sprintf('query_for_uuids_%d', $queryTermIndex);
                    $queryBuilder->orWhere(sprintf('%s.%s = :%s', $entityName, $propertyName, $parameterName))
                        ->setParameter($parameterName, $dqlParameters['uuid_query'], 'uuid' === $propertyConfig['property_data_type'] ? 'uuid' : null);
                } elseif ($propertyConfig['is_ulid'] && $isUlidQueryTerm) {
                    $parameterName = sprintf('query_for_ulids_%d', $queryTermIndex);
                    $queryBuilder->orWhere(sprintf('%s.%s = :%s', $entityName, $propertyName, $parameterName))
                        ->setParameter($parameterName, $dqlParameters['uuid_query'], 'ulid');
                } elseif ($propertyConfig['is_text']) {
                    $parameterName = sprintf('query_for_text_%d', $queryTermIndex);
                    $queryBuilder->orWhere(sprintf('LOWER(%s.%s) LIKE :%s', $entityName, $propertyName, $parameterName))
                        ->setParameter($parameterName, $dqlParameters['text_query']);
                }
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

    private function getSearchablePropertiesConfig(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto): array
    {
        $searchablePropertiesConfig = [];
        $configuredSearchableProperties = $searchDto->getSearchableProperties();
        $searchableProperties = (null === $configuredSearchableProperties || 0 === \count($configuredSearchableProperties)) ? $entityDto->getAllPropertyNames() : $configuredSearchableProperties;

        $entitiesAlreadyJoined = [];
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

                $associatedEntityAlias = $associatedPropertyName = '';
                for ($i = 0; $i < $numAssociatedProperties - 1; ++$i) {
                    $associatedEntityName = $associatedProperties[$i];
                    $associatedEntityAlias = Escaper::escapeDqlAlias($associatedEntityName);
                    $associatedPropertyName = $associatedProperties[$i + 1];

                    if (!\in_array($associatedEntityName, $entitiesAlreadyJoined, true)) {
                        $parentEntityName = 0 === $i ? 'entity' : $associatedProperties[$i - 1];
                        $queryBuilder->leftJoin(Escaper::escapeDqlAlias($parentEntityName).'.'.$associatedEntityName, $associatedEntityAlias);
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

            $isBoolean = 'boolean' === $propertyDataType;
            $isSmallIntegerProperty = 'smallint' === $propertyDataType;
            $isIntegerProperty = 'integer' === $propertyDataType;
            $isNumericProperty = \in_array($propertyDataType, ['number', 'bigint', 'decimal', 'float'], true);
            // 'citext' is a PostgreSQL extension (https://github.com/EasyCorp/EasyAdminBundle/issues/2556)
            $isTextProperty = \in_array($propertyDataType, ['string', 'text', 'citext', 'array', 'simple_array'], true);
            $isGuidProperty = \in_array($propertyDataType, ['guid', 'uuid'], true);
            $isUlidProperty = 'ulid' === $propertyDataType;
            $isJsonProperty = 'json' === $propertyDataType;

            if (!$isBoolean
                && !$isSmallIntegerProperty
                && !$isIntegerProperty
                && !$isNumericProperty
                && !$isTextProperty
                && !$isGuidProperty
                && !$isUlidProperty
                && !$isJsonProperty
            ) {
                $entityFqcn = 'entity' !== $entityName && isset($associatedEntityDto)
                    ? $associatedEntityDto->getFqcn()
                    : $entityDto->getFqcn()
                ;
                /** @var \ReflectionNamedType|\ReflectionUnionType|null $idClassType */
                $idClassType = (new \ReflectionProperty($entityFqcn, $propertyName))->getType();

                if (null !== $idClassType) {
                    $idClassName = $idClassType->getName();

                    if (class_exists($idClassName)) {
                        $isUlidProperty = (new \ReflectionClass($idClassName))->isSubclassOf(Ulid::class);
                        $isGuidProperty = (new \ReflectionClass($idClassName))->isSubclassOf(Uuid::class);
                    }
                }
            }

            $searchablePropertiesConfig[$propertyName] = [
                'entity_name' => $entityName,
                'property_data_type' => $propertyDataType,
                'is_boolean' => $isBoolean,
                'is_small_integer' => $isSmallIntegerProperty,
                'is_integer' => $isIntegerProperty,
                'is_numeric' => $isNumericProperty,
                'is_text' => $isTextProperty,
                'is_guid' => $isGuidProperty,
                'is_ulid' => $isUlidProperty,
                'is_json' => $isJsonProperty,
            ];
        }

        return $searchablePropertiesConfig;
    }
}
