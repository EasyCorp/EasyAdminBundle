<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Search;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class QueryBuilder
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Creates the query builder used to get all the records displayed by the
     * "list" view.
     *
     * @param array       $entityConfig
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param string|null $dqlFilter
     *
     * @return DoctrineQueryBuilder
     */
    public function createListQueryBuilder(array $entityConfig, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        /* @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($entityConfig['class']);
        /* @var ClassMetadata $classMetadata */
        $classMetadata = $em->getClassMetadata($entityConfig['class']);
        /* @var DoctrineQueryBuilder $queryBuilder */
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity')
            ->from($entityConfig['class'], 'entity')
        ;

        $isSortedByDoctrineAssociation = $this->isDoctrineAssociation($classMetadata, $sortField);
        if ($isSortedByDoctrineAssociation) {
            $sortFieldParts = explode('.', $sortField);
            $queryBuilder->leftJoin('entity.'.$sortFieldParts[0], $sortFieldParts[0]);
        }

        if (!empty($dqlFilter)) {
            $queryBuilder->andWhere($dqlFilter);
        }

        if (null !== $sortField) {
            $queryBuilder->orderBy(sprintf('%s%s', $isSortedByDoctrineAssociation ? '' : 'entity.', $sortField), $sortDirection);
        }

        return $queryBuilder;
    }

    /**
     * Creates the query builder used to get the results of the search query
     * performed by the user in the "search" view.
     *
     * @param array       $entityConfig
     * @param string      $searchQuery
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param string|null $dqlFilter
     *
     * @return DoctrineQueryBuilder
     */
    public function createSearchQueryBuilder(array $entityConfig, $searchQuery, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        /* @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($entityConfig['class']);
        /* @var ClassMetadata $classMetadata */
        $classMetadata = $em->getClassMetadata($entityConfig['class']);
        /* @var DoctrineQueryBuilder $queryBuilder */
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity')
            ->from($entityConfig['class'], 'entity')
        ;

        $isSearchQueryNumeric = is_numeric($searchQuery);
        $isSearchQuerySmallInteger = ctype_digit($searchQuery) && $searchQuery >= -32768 && $searchQuery <= 32767;
        $isSearchQueryInteger = ctype_digit($searchQuery) && $searchQuery >= -2147483648 && $searchQuery <= 2147483647;
        $isSearchQueryUuid = 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $searchQuery);
        $lowerSearchQuery = mb_strtolower($searchQuery);

        $queryParameters = [];
        $entitiesAlreadyJoined = [];
        foreach ($entityConfig['search']['fields'] as $fieldName => $metadata) {
            $entityName = 'entity';
            if ($this->isDoctrineAssociation($classMetadata, $fieldName)) {
                // support arbitrarily nested associations (e.g. foo.bar.baz.qux)
                $associationComponents = explode('.', $fieldName);
                for ($i = 0; $i < \count($associationComponents) - 1; ++$i) {
                    $associatedEntityName = $associationComponents[$i];
                    $associatedFieldName = $associationComponents[$i + 1];

                    if (!\in_array($associatedEntityName, $entitiesAlreadyJoined)) {
                        $parentEntityName = 0 === $i ? 'entity' : $associationComponents[$i - 1];
                        $queryBuilder->leftJoin($parentEntityName.'.'.$associatedEntityName, $associatedEntityName);
                        $entitiesAlreadyJoined[] = $associatedEntityName;
                    }

                    $entityName = $associatedEntityName;
                    $fieldName = $associatedFieldName;
                }
            }

            $isSmallIntegerField = 'smallint' === $metadata['dataType'];
            $isIntegerField = 'integer' === $metadata['dataType'];
            $isNumericField = \in_array($metadata['dataType'], ['number', 'bigint', 'decimal', 'float']);
            // 'citext' is a PostgreSQL extension (https://github.com/EasyCorp/EasyAdminBundle/issues/2556)
            $isTextField = \in_array($metadata['dataType'], ['string', 'text', 'citext', 'array', 'simple_array']);
            $isGuidField = \in_array($metadata['dataType'], ['guid', 'uuid']);

            // this complex condition is needed to avoid issues on PostgreSQL databases
            if (
                ($isSmallIntegerField && $isSearchQuerySmallInteger) ||
                ($isIntegerField && $isSearchQueryInteger) ||
                ($isNumericField && $isSearchQueryNumeric)
            ) {
                $queryBuilder->orWhere(sprintf('%s.%s = :numeric_query', $entityName, $fieldName));
                // adding '0' turns the string into a numeric value
                $queryParameters['numeric_query'] = 0 + $searchQuery;
            } elseif ($isGuidField && $isSearchQueryUuid) {
                $queryBuilder->orWhere(sprintf('%s.%s = :uuid_query', $entityName, $fieldName));
                $queryParameters['uuid_query'] = $searchQuery;
            } elseif ($isTextField) {
                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) LIKE :fuzzy_query', $entityName, $fieldName));
                $queryParameters['fuzzy_query'] = '%'.$lowerSearchQuery.'%';

                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) IN (:words_query)', $entityName, $fieldName));
                $queryParameters['words_query'] = explode(' ', $lowerSearchQuery);
            }
        }

        if (0 !== \count($queryParameters)) {
            $queryBuilder->setParameters($queryParameters);
        }

        if (!empty($dqlFilter)) {
            $queryBuilder->andWhere($dqlFilter);
        }

        $isSortedByDoctrineAssociation = $this->isDoctrineAssociation($classMetadata, $sortField);
        if ($isSortedByDoctrineAssociation) {
            $associatedEntityName = explode('.', $sortField)[0];
            if (!\in_array($associatedEntityName, $entitiesAlreadyJoined)) {
                $queryBuilder->leftJoin('entity.'.$associatedEntityName, $associatedEntityName);
                $entitiesAlreadyJoined[] = $associatedEntityName;
            }
        }

        if (null !== $sortField) {
            $queryBuilder->orderBy(sprintf('%s%s', $isSortedByDoctrineAssociation ? '' : 'entity.', $sortField), $sortDirection ?: 'DESC');
        }

        return $queryBuilder;
    }

    /**
     * Checking if the field name contains a '.' is not enough to decide if it's a
     * Doctrine association. This also happens when using embedded classes, so the
     * embeddedClasses property from Doctrine class metadata must be checked too.
     *
     * @param ClassMetadata $classMetadata
     * @param string|null   $fieldName
     *
     * @return bool
     */
    protected function isDoctrineAssociation(ClassMetadata $classMetadata, $fieldName)
    {
        if (null === $fieldName) {
            return false;
        }

        $fieldNameParts = explode('.', $fieldName);

        return false !== strpos($fieldName, '.') && !\array_key_exists($fieldNameParts[0], $classMetadata->embeddedClasses);
    }
}
