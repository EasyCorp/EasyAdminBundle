<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory\EntityFactoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\NestedAssociationResolverInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ResolvedPropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntitySearchEvent;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class EntityRepository implements EntityRepositoryInterface, NestedAssociationResolverInterface
{
    /**
     * Associations already joined by resolveNestedAssociations(), indexed by their
     * property path. The map is keyed by query builder so that no state is shared
     * between different queries (or requests, in long-running PHP runtimes).
     *
     * @var \WeakMap<QueryBuilder, array<string, string>>
     */
    private \WeakMap $joinedAssociations;

    public function __construct(
        private AdminContextProviderInterface $adminContextProvider,
        private ManagerRegistry $doctrine,
        private EntityFactoryInterface $entityFactory,
        private FormFactory $formFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        /** @var \WeakMap<QueryBuilder, array<string, string>> $joinedAssociations */
        $joinedAssociations = new \WeakMap();
        $this->joinedAssociations = $joinedAssociations;
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
        // customSort comes from the URL and is validated; defaultSort comes from
        // Crud::setDefaultSort() and is trusted. A rejected customSort entry must
        // still leave the developer-supplied default for the same property in place
        $validatedCustomSort = array_filter(
            $searchDto->getCustomSort(),
            fn (string $sortOrder, string $sortProperty): bool => $this->isValidCustomSort($sortProperty, $sortOrder, $entityDto, $fields),
            \ARRAY_FILTER_USE_BOTH,
        );

        // array union preserves keys from the left operand, so customSort wins
        // over defaultSort for the same property — same precedence as SearchDto::getSort()
        foreach ($validatedCustomSort + $searchDto->getDefaultSort() as $sortProperty => $sortOrder) {
            $this->applyOrderClause($queryBuilder, $entityDto, $fields, $sortProperty, $sortOrder);
        }
    }

    private function applyOrderClause(QueryBuilder $queryBuilder, EntityDto $entityDto, FieldCollection $fields, string $sortProperty, string $sortOrder): void
    {
        $sortOrder = $this->normalizeSortOrder($sortOrder);
        $sortFieldIsDoctrineAssociation = $this->isAssociation($entityDto, $sortProperty);

        if ($sortFieldIsDoctrineAssociation) {
            if (str_contains($sortProperty, '.')) {
                $pathToResolve = $sortProperty;
                $pathEndsInAssociation = $this->pathEndsInSingleValuedAssociation($entityDto, $sortProperty);

                if ($pathEndsInAssociation) {
                    // an AssociationField with a nested property (e.g. 'customer.country') sorts by the
                    // property defined with setSortProperty() or, by default, by the foreign key of the
                    // leaf association on its parent (mirroring the 'entity.assoc' single-level behavior)
                    $associationSortProperty = $fields->getByProperty($sortProperty)?->getCustomOption(AssociationField::OPTION_SORT_PROPERTY);
                    if (null !== $associationSortProperty) {
                        $pathToResolve = $sortProperty.'.'.$associationSortProperty;
                        $pathEndsInAssociation = false;
                    }
                }

                $resolvedProperty = $this->resolveNestedAssociations($queryBuilder, $entityDto, $pathToResolve, $pathEndsInAssociation);
                $queryBuilder->addOrderBy($resolvedProperty->getEntityAlias().'.'.$resolvedProperty->getPropertyName(), $sortOrder);

                return;
            }

            // check if join has been added once before.
            if (!\in_array($sortProperty, $queryBuilder->getAllAliases(), true)) {
                $queryBuilder->leftJoin('entity.'.$sortProperty, $sortProperty);
            }

            // register the join in the shared cache so that resolveNestedAssociations() reuses it
            // when another sort property (e.g. a nested defaultSort) traverses the same association
            $joinedAssociations = $this->joinedAssociations[$queryBuilder] ?? [];
            if (!isset($joinedAssociations[$sortProperty])) {
                $joinedAssociations[$sortProperty] = $sortProperty;
                $this->joinedAssociations[$queryBuilder] = $joinedAssociations;
            }

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
            $queryBuilder->addOrderBy('entity.'.$sortProperty, $sortOrder);
        }
    }

    private function normalizeSortOrder(string $sortOrder): \SortDirection|string
    {
        $sortOrder = strtoupper($sortOrder);

        if (!self::queryBuilderAcceptsSortDirection()) {
            return $sortOrder;
        }

        return SortOrder::DESC === $sortOrder ? \SortDirection::Descending : \SortDirection::Ascending;
    }

    // Doctrine ORM 3.7 deprecated string directions in favor of the PHP 8.6 \SortDirection enum.
    // Checking that the enum exists is not enough: on PHP 8.6 (or with symfony/polyfill-php86
    // installed) it also exists next to ORM 2.20/3.6, whose addOrderBy() only accepts strings
    private static function queryBuilderAcceptsSortDirection(): bool
    {
        static $acceptsSortDirection = null;

        if (null === $acceptsSortDirection) {
            $orderParameterType = (new \ReflectionMethod(QueryBuilder::class, 'addOrderBy'))->getParameters()[1]->getType();
            $acceptsSortDirection = null !== $orderParameterType && str_contains((string) $orderParameterType, 'SortDirection');
        }

        return $acceptsSortDirection;
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
            $originalPropertyName = str_replace(FiltersFormType::EMBEDDED_PROPERTY_SEPARATOR, '.', $propertyName);

            $filter = $configuredFilters->get($originalPropertyName);
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

            try {
                $resolvedProperty = $this->resolveNestedAssociations($queryBuilder, $entityDto, $originalPropertyName, is_a($filter->getFqcn(), EntityFilter::class, true));
            } catch (\InvalidArgumentException) {
                // needed to support custom filters that use unmapped property names
                $resolvedProperty = null;
            }

            $filterDataDto = FilterDataDto::new($i, $filter, $rootAlias, $submittedData, $resolvedProperty);
            $filter->apply($queryBuilder, $filterDataDto, $fields->getByProperty($originalPropertyName), $entityDto);

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

        foreach ($searchableProperties as $searchableProperty) {
            try {
                $resolvedProperty = $this->resolveNestedAssociations($queryBuilder, $entityDto, $searchableProperty);
            } catch (\InvalidArgumentException) {
                // skip properties not mapped by Doctrine (e.g. virtual fields) instead of failing the whole search
                continue;
            }

            // In Doctrine ORM 3.x, FieldMapping implements \ArrayAccess; in 4.x it's an object with properties
            $fieldMapping = $resolvedProperty->getEntityDto()->getClassMetadata()->getFieldMapping($resolvedProperty->getPropertyName());
            // In Doctrine ORM 2.x, getFieldMapping() returns an array
            /** @phpstan-ignore-next-line function.impossibleType */
            if (\is_array($fieldMapping)) {
                /** @phpstan-ignore-next-line cast.useless */
                $fieldMapping = (object) $fieldMapping;
            }
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            $propertyDataType = property_exists($fieldMapping, 'type') ? $fieldMapping->type : $fieldMapping['type'];

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
                $entityFqcn = $resolvedProperty->getEntityDto()->getFqcn();

                /** @var \ReflectionNamedType|\ReflectionUnionType|null $idClassType */
                $idClassType = null;
                $reflectionClass = new \ReflectionClass($entityFqcn);

                // this is needed to handle inherited properties
                while (false !== $reflectionClass) {
                    if ($reflectionClass->hasProperty($resolvedProperty->getPropertyName())) {
                        $reflection = $reflectionClass->getProperty($resolvedProperty->getPropertyName());
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
                'entity_name' => $resolvedProperty->getEntityAlias() ?? 'entity',
                'property_data_type' => $propertyDataType,
                'property_name' => $resolvedProperty->getPropertyName(),
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

    /**
     * Supports arbitrarily nested associations (e.g. foo.bar.baz.qux), including
     * paths that traverse Doctrine embeddables. When a query builder is given, the
     * needed JOIN clauses are added to it (each association is joined only once,
     * even when resolving multiple property paths for the same query builder).
     */
    public function resolveNestedAssociations(?QueryBuilder $queryBuilder, EntityDto $rootEntityDto, string $propertyName, bool $mustEndWithAssociation = false): ResolvedPropertyDto
    {
        $associatedProperties = explode('.', $propertyName);
        $numAssociatedProperties = \count($associatedProperties);
        $resolvedEntityDto = $rootEntityDto;
        $joinedAssociations = null !== $queryBuilder ? ($this->joinedAssociations[$queryBuilder] ?? []) : [];
        $parentEntityAlias = null;
        if (null !== $queryBuilder) {
            $rootAliases = $queryBuilder->getRootAliases();
            $parentEntityAlias = [] === $rootAliases ? 'entity' : $rootAliases[0];
        }
        $fullPropertyName = $compoundPropertyName = $resolvedPropertyName = '';

        for ($i = 0; $i < $numAssociatedProperties; ++$i) {
            $resolvedPropertyName = trim($compoundPropertyName.'.'.$associatedProperties[$i], '.');
            $fullPropertyName = trim($fullPropertyName.'.'.$resolvedPropertyName, '.');

            if ($this->isAssociation($resolvedEntityDto, $resolvedPropertyName)) {
                if ($i === $numAssociatedProperties - 1) {
                    if (!$mustEndWithAssociation) {
                        throw new \InvalidArgumentException(sprintf('The "%s" property is not valid. When using associated properties, you must also define the exact field to target (e.g. "%s.id", "%s.name", etc.)', $propertyName, $propertyName, $propertyName));
                    }

                    // when the last property of the path is an association, no JOIN is
                    // needed because callers compare against the association itself
                    continue;
                }

                if (null !== $queryBuilder && null !== $parentEntityAlias) {
                    if (!isset($joinedAssociations[$fullPropertyName])) {
                        $aliasIndex = \count($joinedAssociations);
                        $joinedAssociations[$fullPropertyName] = Escaper::escapeDqlAlias($resolvedPropertyName.(0 === $aliasIndex ? '' : $aliasIndex));
                        $queryBuilder->leftJoin($parentEntityAlias.'.'.$resolvedPropertyName, $joinedAssociations[$fullPropertyName]);
                        $this->joinedAssociations[$queryBuilder] = $joinedAssociations;
                    }

                    $parentEntityAlias = $joinedAssociations[$fullPropertyName];
                }

                $resolvedEntityDto = $this->entityFactory->create($resolvedEntityDto->getClassMetadata()->getAssociationTargetClass($resolvedPropertyName));
                $compoundPropertyName = '';
            } else {
                // Normal & Embedded class properties
                $compoundPropertyName = $resolvedPropertyName;
            }
        }

        if (!$mustEndWithAssociation && !isset($resolvedEntityDto->getClassMetadata()->fieldMappings[$resolvedPropertyName])) {
            throw new \InvalidArgumentException(sprintf('The "%s" property is not valid. The field "%s" does not exist in "%s".', $propertyName, $resolvedPropertyName, $propertyName));
        }

        return new ResolvedPropertyDto($resolvedEntityDto, $parentEntityAlias, $resolvedPropertyName);
    }

    private function isAssociation(EntityDto $entityDto, string $propertyName): bool
    {
        $propertyNameParts = explode('.', $propertyName, 2);

        return $entityDto->getClassMetadata()->hasAssociation($propertyNameParts[0]);
    }

    private function pathEndsInSingleValuedAssociation(EntityDto $entityDto, string $propertyName): bool
    {
        try {
            $resolvedProperty = $this->resolveNestedAssociations(null, $entityDto, $propertyName, true);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return $resolvedProperty->getEntityDto()->getClassMetadata()->isSingleValuedAssociation($resolvedProperty->getPropertyName());
    }

    private function isValidCustomSort(string $sortProperty, string $sortOrder, EntityDto $entityDto, FieldCollection $fields): bool
    {
        // the order direction reaches DQL via Expr\OrderBy as "$property $direction",
        // so an unvalidated value can smuggle a second ORDER BY column (e.g. "ASC, x DESC")
        $direction = strtoupper($sortOrder);
        if (SortOrder::ASC !== $direction && SortOrder::DESC !== $direction) {
            return false;
        }

        $classMetadata = $entityDto->getClassMetadata();

        // structural gate: the property must be a real Doctrine field or association,
        // or a nested path whose every segment maps to one, ending either in a field
        // (e.g. "author.publisher.name") or in a single-valued association (e.g.
        // "customer.country"). This also rejects any key with characters (commas, spaces,
        // quotes…) that could otherwise smuggle DQL fragments through identifier
        // interpolation. Embeddable properties (e.g. "address.city") are real Doctrine
        // fields with a dotted name (hasField() === true), so they don't need to be resolved
        if (str_contains($sortProperty, '.') && !$classMetadata->hasField($sortProperty)) {
            try {
                $this->resolveNestedAssociations(null, $entityDto, $sortProperty);
            } catch (\InvalidArgumentException) {
                if (!$this->pathEndsInSingleValuedAssociation($entityDto, $sortProperty)) {
                    return false;
                }
            }
        } elseif (!$classMetadata->hasField($sortProperty) && !$classMetadata->hasAssociation($sortProperty)) {
            return false;
        }

        $fieldDto = $fields->getByProperty($sortProperty);
        if (null === $fieldDto || false === $fieldDto->isSortable()) {
            return false;
        }

        return true;
    }
}
