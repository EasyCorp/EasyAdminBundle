<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

final class EntityRepository implements EntityRepositoryInterface
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function createQueryBuilder(SearchDto $searchDto, EntityDto $entityDto): QueryBuilder
    {
        $entityManager = $this->doctrine->getManagerForClass($entityDto->getFqcn());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('entity')
            ->from($entityDto->getFqcn(), 'entity')
        ;

        if (null !== $searchDto->getQuery()) {
            $this->addSearchClause($queryBuilder, $searchDto, $entityDto);
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
        $isUuidQuery = 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $query);

        $dqlParams = [
            // adding '0' turns the string into a numeric value
            'numeric_query' => is_numeric($query) ? 0 + $query : $query,
            'uuid_query' => $query,
            'text_query' => '%'.$lowercaseQuery.'%',
            'words_query' => explode(' ', $lowercaseQuery),
        ];

        $entitiesAlreadyJoined = [];
        foreach ($searchDto->getSearchableFields() as $field) {
            $entityName = 'entity';
            $PropertyDataType = $entityDto->getDataType($field->getProperty());
            $propertyName = $field->getProperty();

            if ($entityDto->isAssociation($field->getProperty())) {
                // support arbitrarily nested associations (e.g. foo.bar.baz.qux)
                $associatedProperties = explode('.', $propertyName);
                for ($i = 0; $i < \count($associatedProperties) - 1; ++$i) {
                    $associatedEntityName = $associatedProperties[$i];
                    $associatedPropertyName = $associatedProperties[$i + 1];

                    if (!\in_array($associatedEntityName, $entitiesAlreadyJoined)) {
                        $parentEntityName = 0 === $i ? 'entity' : $associatedProperties[$i - 1];
                        $queryBuilder->leftJoin($parentEntityName.'.'.$associatedEntityName, $associatedEntityName);
                        $entitiesAlreadyJoined[] = $associatedEntityName;
                    }

                    $entityName = $associatedEntityName;
                    $propertyName = $associatedPropertyName;
                }
            }

            $isSmallIntegerProperty = 'smallint' === $PropertyDataType;
            $isIntegerProperty = 'integer' === $PropertyDataType;
            $isNumericProperty = \in_array($PropertyDataType, ['number', 'bigint', 'decimal', 'float']);
            // 'citext' is a PostgreSQL extension (https://github.com/EasyCorp/EasyAdminBundle/issues/2556)
            $isTextProperty = \in_array($PropertyDataType, ['string', 'text', 'citext', 'array', 'simple_array']);
            $isGuidProperty = \in_array($PropertyDataType, ['guid', 'uuid']);

            // this complex condition is needed to avoid issues on PostgreSQL databases
            if (
                ($isSmallIntegerProperty && $isSmallIntegerQuery) ||
                ($isIntegerProperty && $isIntegerQuery) ||
                ($isNumericProperty && $isNumericQuery)
            ) {
                $queryBuilder->orWhere(sprintf('%s.%s = :query_for_numbers', $entityName, $propertyName))
                    ->setParameter('query_for_numbers', $dqlParams['numeric_query']);
            } elseif ($isGuidProperty && $isUuidQuery) {
                $queryBuilder->orWhere(sprintf('%s.%s = :query_for_uuids', $entityName, $propertyName))
                    ->setParameter('query_for_uuids', $dqlParams['uuid_query']);
            } elseif ($isTextProperty) {
                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) LIKE :query_for_text', $entityName, $propertyName))
                    ->setParameter('query_for_text', $dqlParams['text_query']);
                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) IN (:query_as_words)', $entityName, $propertyName))
                    ->setParameter('query_as_words', $dqlParams['words_query']);
            }
        }
    }

    private function addOrderClause(QueryBuilder $queryBuilder, SearchDto $searchDto, EntityDto $entityDto): void
    {
        foreach ($searchDto->getSort() as $sortField => $sortOrder) {
            $sortFieldIsDoctrineAssociation = $entityDto->isAssociation($sortField);

            if ($sortFieldIsDoctrineAssociation) {
                $sortFieldParts = explode('.', $sortField, 2);
                $queryBuilder->leftJoin('entity.'.$sortFieldParts[0], $sortFieldParts[0]);
                $queryBuilder->addOrderBy($sortField, $sortOrder);
            } else {
                $queryBuilder->addOrderBy('entity.'.$sortField, $sortOrder);
            }
        }
    }
}
