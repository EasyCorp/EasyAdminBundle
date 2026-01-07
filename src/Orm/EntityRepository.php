<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntitySearchEvent;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityRepository implements EntityRepositoryInterface
{
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly ManagerRegistry $doctrine,
        private readonly EntityFactory $entityFactory,
        private readonly FormFactory $formFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
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
            try {
                $databasePlatform = $entityManager->getConnection()->getDatabasePlatform();
            } catch (\Throwable) {
                $databasePlatform = null;
            }
            $databasePlatformFqcn = null !== $databasePlatform ? $databasePlatform::class : '';

            $this->addSearchClause($queryBuilder, $searchDto, $entityDto, $databasePlatformFqcn);
        }

        $appliedFilters = $searchDto->getAppliedFilters();
        if (null !== $appliedFilters && 0 !== \count($appliedFilters)) {
            $this->addFilterClause($queryBuilder, $searchDto, $entityDto, $filters, $fields);
        }

        $this->addOrderClause($queryBuilder, $searchDto, $entityDto, $fields);

        return $queryBuilder;
    }

    private function addSearchClause(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto, string $databasePlatformFqcn): void
    {
        $isPostgreSql = PostgreSQLPlatform::class === $databasePlatformFqcn || is_subclass_of($databasePlatformFqcn, PostgreSQLPlatform::class);
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

            $queryTermConditions = new Orx();
            foreach ($searchablePropertiesConfig as $propertyConfig) {
                $entityName = $propertyConfig['entity_name'];

                // this complex condition is needed to avoid issues on PostgreSQL databases
                if (
                    ($propertyConfig['is_small_integer'] && $isSmallIntegerQueryTerm)
                    || ($propertyConfig['is_integer'] && $isIntegerQueryTerm)
                    || ($propertyConfig['is_numeric'] && $isNumericQueryTerm)
                ) {
                    $parameterName = sprintf('query_for_numbers_%d', $queryTermIndex);
                    $queryTermConditions->add(sprintf('%s.%s = :%s', $entityName, $propertyConfig['property_name'], $parameterName));
                    $queryBuilder->setParameter($parameterName, $dqlParameters['numeric_query']);
                } elseif ($propertyConfig['is_guid'] && $isUuidQueryTerm) {
                    $parameterName = sprintf('query_for_uuids_%d', $queryTermIndex);
                    $queryTermConditions->add(sprintf('%s.%s = :%s', $entityName, $propertyConfig['property_name'], $parameterName));
                    $queryBuilder->setParameter($parameterName, $dqlParameters['uuid_query'], 'uuid' === $propertyConfig['property_data_type'] ? 'uuid' : null);
                } elseif ($propertyConfig['is_ulid'] && $isUlidQueryTerm) {
                    $parameterName = sprintf('query_for_ulids_%d', $queryTermIndex);
                    $queryTermConditions->add(sprintf('%s.%s = :%s', $entityName, $propertyConfig['property_name'], $parameterName));
                    $queryBuilder->setParameter($parameterName, $dqlParameters['uuid_query'], 'ulid');
                } elseif ($propertyConfig['is_text']) {
                    $parameterName = sprintf('query_for_text_%d', $queryTermIndex);
                    // concatenating an empty string is needed to avoid issues on PostgreSQL databases (https://github.com/EasyCorp/EasyAdminBundle/issues/6290)
                    $queryTermConditions->add(sprintf('LOWER(CONCAT(%s.%s, \'\')) LIKE :%s', $entityName, $propertyConfig['property_name'], $parameterName));
                    $queryBuilder->setParameter($parameterName, $dqlParameters['text_query']);
                } elseif ($propertyConfig['is_json'] && !$isPostgreSql) {
                    // neither LOWER() nor LIKE() are supported for JSON columns by all PostgreSQL installations
                    $parameterName = sprintf('query_for_text_%d', $queryTermIndex);
                    $queryTermConditions->add(sprintf('LOWER(%s.%s) LIKE :%s', $entityName, $propertyConfig['property_name'], $parameterName));
                    $queryBuilder->setParameter($parameterName, $dqlParameters['text_query']);
                }
            }

            // When no fields are queried, the current condition must not yield any results
            if (0 === $queryTermConditions->count()) {
                $queryTermConditions->add('0 = 1');
            }

            if (SearchMode::ALL_TERMS === $searchDto->getSearchMode()) {
                $queryBuilder->andWhere($queryTermConditions);
            } else {
                $queryBuilder->orWhere($queryTermConditions);
            }
        }

        $this->eventDispatcher->dispatch(new AfterEntitySearchEvent($queryBuilder, $searchDto, $entityDto));
    }

    private function addOrderClause(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields): void
    {
        foreach ($searchDto->getSort() as $sortProperty => $sortOrder) {
            $aliases = $queryBuilder->getAllAliases();
            $sortFieldIsDoctrineAssociation = $this->isAssociation($entityDto, $sortProperty);

            if ($sortFieldIsDoctrineAssociation) {
                $sortFieldParts = explode('.', $sortProperty, 2);
                // check if join has been added once before.
                if (!\in_array($sortFieldParts[0], $aliases, true)) {
                    $queryBuilder->leftJoin('entity.'.$sortFieldParts[0], $sortFieldParts[0]);
                }

                if (1 === \count($sortFieldParts)) {
                    if ($entityDto->getClassMetadata()->isCollectionValuedAssociation($sortProperty)) {
                        /** @var EntityManagerInterface $entityManager */
                        $entityManager = $this->doctrine->getManagerForClass($entityDto->getFqcn());
                        $countQueryBuilder = $entityManager->createQueryBuilder();

                        if (ClassMetadata::MANY_TO_MANY === $entityDto->getClassMetadata()->getAssociationMapping($sortProperty)['type']) {
                            // many-to-many relation
                            $countQueryBuilder
                                ->select($queryBuilder->expr()->count('subQueryEntity'))
                                ->from($entityDto->getFqcn(), 'subQueryEntity')
                                ->join(sprintf('subQueryEntity.%s', $sortProperty), 'relatedEntity')
                                ->where('subQueryEntity = entity');
                        } else {
                            // one-to-many relation
                            $countQueryBuilder
                                ->select($queryBuilder->expr()->count('subQueryEntity'))
                                ->from($entityDto->getClassMetadata()->getAssociationTargetClass($sortProperty), 'subQueryEntity')
                                ->where(sprintf('subQueryEntity.%s = entity', $entityDto->getClassMetadata()->getAssociationMapping($sortProperty)['mappedBy']));
                        }

                        $queryBuilder->addSelect(sprintf('(%s) as HIDDEN sub_query_sort', $countQueryBuilder->getDQL()));
                        $queryBuilder->addOrderBy('sub_query_sort', $sortOrder);
                        $queryBuilder->addOrderBy('entity.'.$entityDto->getClassMetadata()->getSingleIdentifierFieldName(), $sortOrder);
                    } else {
                        $field = $fields->getByProperty($sortProperty);
                        $associationSortProperty = $field?->getCustomOption(AssociationField::OPTION_SORT_PROPERTY);

                        if (null === $associationSortProperty) {
                            $queryBuilder->addOrderBy('entity.'.$sortProperty, $sortOrder);
                        } else {
                            $queryBuilder->addOrderBy($sortProperty.'.'.$associationSortProperty, $sortOrder);
                        }
                    }
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

            /** @var string $rootAlias */
            $rootAlias = current($queryBuilder->getRootAliases());

            $filterDataDto = FilterDataDto::new($i, $filter, $rootAlias, $submittedData);
            $filter->apply($queryBuilder, $filterDataDto, $fields->getByProperty($propertyName), $entityDto);

            ++$i;
        }
    }

    /**
     * @return array<array{
     *     entity_name: string,
     *     property_data_type: string,
     *     property_name: string,
     *     is_boolean: bool,
     *     is_small_integer: bool,
     *     is_integer: bool,
     *     is_numeric: bool,
     *     is_text: bool,
     *     is_guid: bool,
     *     is_ulid: bool,
     *     is_json: bool,
     * }>
     */
    private function getSearchablePropertiesConfig(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto): array
    {
        $searchablePropertiesConfig = [];
        $configuredSearchableProperties = $searchDto->getSearchableProperties();
        $searchableProperties = (null === $configuredSearchableProperties || 0 === \count($configuredSearchableProperties)) ? $entityDto->getClassMetadata()->getFieldNames() : $configuredSearchableProperties;

        $entitiesAlreadyJoined = [];
        foreach ($searchableProperties as $propertyName) {
            if ($this->isAssociation($entityDto, $propertyName)) {
                // support arbitrarily nested associations (e.g. foo.bar.baz.qux)
                $associatedProperties = explode('.', $propertyName);
                $numAssociatedProperties = \count($associatedProperties);

                if (1 === $numAssociatedProperties) {
                    throw new \InvalidArgumentException(sprintf('The "%s" property included in the setSearchFields() method is not a valid search field. When using associated properties in search, you must also define the exact field used in the search (e.g. \'%s.id\', \'%s.name\', etc.)', $propertyName, $propertyName, $propertyName));
                }

                $associatedEntityDto = $this->entityFactory->create($entityDto->getClassMetadata()->getAssociationTargetClass($associatedProperties[0]));

                $associatedEntityAlias = $associatedPropertyName = '';
                for ($i = 0; $i < $numAssociatedProperties - 1; ++$i) {
                    $associatedEntityName = $associatedProperties[$i];
                    $associatedEntityAlias = $entitiesAlreadyJoined[$associatedEntityName] ?? Escaper::escapeDqlAlias($associatedEntityName).(0 === $i ? '' : $i);
                    $associatedPropertyName = $associatedProperties[$i + 1];

                    if (!\in_array($associatedEntityAlias, $entitiesAlreadyJoined, true)) {
                        $parentEntityName = 0 === $i ? 'entity' : $entitiesAlreadyJoined[$associatedProperties[$i - 1]];
                        $queryBuilder->leftJoin(Escaper::escapeDqlAlias($parentEntityName).'.'.$associatedEntityName, $associatedEntityAlias);
                        $entitiesAlreadyJoined[$associatedEntityName] = $associatedEntityAlias;
                    }

                    if ($i < $numAssociatedProperties - 2) {
                        $targetEntity = $associatedEntityDto->getClassMetadata()->getAssociationTargetClass($associatedPropertyName);
                        $associatedEntityDto = $this->entityFactory->create($targetEntity);
                    }
                }

                $entityName = $associatedEntityAlias;
                $propertyName = $associatedPropertyName;
                if (!isset($associatedEntityDto->getClassMetadata()->fieldMappings[$propertyName])) {
                    throw new \InvalidArgumentException(sprintf('The "%s" property included in the setSearchFields() method is not a valid search field. When using associated properties in search, you must also define the exact field used in the search (e.g. \'%s.id\', \'%s.name\', etc.)', $propertyName, $propertyName, $propertyName));
                }

                // In Doctrine ORM 3.x, FieldMapping implements \ArrayAccess; in 4.x it's an object with properties
                $fieldMapping = $associatedEntityDto->getClassMetadata()->getFieldMapping($propertyName);
                // In Doctrine ORM 2.x, getFieldMapping() returns an array
                /** @phpstan-ignore-next-line function.impossibleType */
                if (\is_array($fieldMapping)) {
                    /** @phpstan-ignore-next-line cast.useless */
                    $fieldMapping = (object) $fieldMapping;
                }
                /** @phpstan-ignore-next-line function.alreadyNarrowedType */
                $propertyDataType = property_exists($fieldMapping, 'type') ? $fieldMapping->type : $fieldMapping['type'];
            } else {
                $entityName = 'entity';
                if (!isset($entityDto->getClassMetadata()->fieldMappings[$propertyName])) {
                    throw new \InvalidArgumentException(sprintf('The "%s" property included in the setSearchFields() method is not a valid search field. When using associated properties in search, you must also define the exact field used in the search (e.g. \'%s.id\', \'%s.name\', etc.)', $propertyName, $propertyName, $propertyName));
                }

                // In Doctrine ORM 3.x, FieldMapping implements \ArrayAccess; in 4.x it's an object with properties
                $fieldMapping = $entityDto->getClassMetadata()->getFieldMapping($propertyName);
                // In Doctrine ORM 2.x, getFieldMapping() returns an array
                /** @phpstan-ignore-next-line function.impossibleType */
                if (\is_array($fieldMapping)) {
                    /** @phpstan-ignore-next-line cast.useless */
                    $fieldMapping = (object) $fieldMapping;
                }
                /** @phpstan-ignore-next-line function.alreadyNarrowedType */
                $propertyDataType = property_exists($fieldMapping, 'type') ? $fieldMapping->type : $fieldMapping['type'];
            }

            $isBoolean = 'boolean' === $propertyDataType;
            $isSmallIntegerProperty = 'smallint' === $propertyDataType;
            $isIntegerProperty = 'integer' === $propertyDataType;
            $isNumericProperty = \in_array($propertyDataType, ['number', 'bigint', 'decimal', 'float'], true);
            // 'citext' is a PostgreSQL extension (https://github.com/EasyCorp/EasyAdminBundle/issues/2556)
            $isTextProperty = \in_array($propertyDataType, ['ascii_string', 'string', 'text', 'citext', 'array', 'simple_array'], true);
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
                $idClassType = null;
                $reflectionClass = new \ReflectionClass($entityFqcn);

                // this is needed to handle inherited properties
                while (false !== $reflectionClass) {
                    if ($reflectionClass->hasProperty($propertyName)) {
                        $reflection = $reflectionClass->getProperty($propertyName);
                        $idClassType = $reflection->getType();
                        break;
                    }
                    $reflectionClass = $reflectionClass->getParentClass();
                }

                if (null !== $idClassType) {
                    /** @var \ReflectionNamedType|\ReflectionUnionType $idClassType */
                    $idClassName = $idClassType->getName();

                    if (class_exists($idClassName)) {
                        $isUlidProperty = (new \ReflectionClass($idClassName))->isSubclassOf(Ulid::class);
                        $isGuidProperty = (new \ReflectionClass($idClassName))->isSubclassOf(Uuid::class);
                    }
                }
            }

            $searchablePropertiesConfig[] = [
                'entity_name' => $entityName,
                'property_data_type' => $propertyDataType,
                'property_name' => $propertyName,
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

    private function isAssociation(EntityDto $entityDto, string $propertyName): bool
    {
        if ($entityDto->getClassMetadata()->hasAssociation($propertyName)) {
            return true;
        }

        if (!str_contains($propertyName, '.')) {
            return false;
        }

        $propertyNameParts = explode('.', $propertyName, 2);

        return !isset($entityDto->getClassMetadata()->embeddedClasses[$propertyNameParts[0]]);
    }
}
