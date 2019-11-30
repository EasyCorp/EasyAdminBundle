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

        if (null !== $query = $searchDto->getQuery()) {
            $this->addSearchRestrictions($entityDto, $queryBuilder, $query);
        }

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

        return $queryBuilder;
    }

    private function addSearchRestrictions(EntityDto $entityDto, QueryBuilder $queryBuilder, $query): void
    {
        $lowercaseQuery = mb_strtolower($query);
        $isSearchQueryNumeric = is_numeric($query);
        $isSearchQuerySmallInteger = ctype_digit($query) && $query >= -32768 && $query <= 32767;
        $isSearchQueryInteger = ctype_digit($query) && $query >= -2147483648 && $query <= 2147483647;
        $isSearchQueryUuid = 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $query);

        $dqlParams = [
            // adding '0' turns the string into a numeric value
            'query_for_numbers' => is_numeric($query) ? 0 + $query : $query,
            'query_for_uuids' => $query,
            'query_for_text' => '%'.$lowercaseQuery.'%',
            'query_as_words' => explode(' ', $lowercaseQuery),
        ];

        $entitiesAlreadyJoined = [];
        // TODO: get the real search fields and their metadata
        $entityConfig = ['name' => ['dataType' => 'string'], 'description' => ['dataType' => 'string']];
        foreach ($entityConfig as $fieldName => $metadata) {
            $entityName = 'entity';
            if ($entityDto->isAssociation($fieldName)) {
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
                $queryBuilder->orWhere(sprintf('%s.%s = :query_for_numbers', $entityName, $fieldName))
                    ->setParameter('query_for_numbers', $dqlParams['query_for_numbers']);
            } elseif ($isGuidField && $isSearchQueryUuid) {
                $queryBuilder->orWhere(sprintf('%s.%s = :query_for_uuids', $entityName, $fieldName))
                    ->setParameter('query_for_uuids', $dqlParams['query_for_uuids']);
            } elseif ($isTextField) {
                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) LIKE :query_for_text', $entityName, $fieldName))
                    ->setParameter('query_for_text', $dqlParams['query_for_text']);
                $queryBuilder->orWhere(sprintf('LOWER(%s.%s) IN (:query_as_words)', $entityName, $fieldName))
                    ->setParameter('query_as_words', $dqlParams['query_as_words']);
            }
        }
    }
}
