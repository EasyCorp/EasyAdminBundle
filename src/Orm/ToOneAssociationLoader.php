<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;

/**
 * Loads the to-one associations displayed for a list of entities (e.g. the rows of an
 * index page) with a single query per associated entity, instead of one query per row
 * when each lazy-loaded associated entity is rendered.
 *
 * Doctrine's eager fetch mode is not used because its deferred loading is flushed by any
 * nested hydration (e.g. the inverse side of a one-to-one association, which Doctrine
 * always loads when hydrating each row), so it often ends up running one query per row.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal
 */
final class ToOneAssociationLoader
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    /**
     * @param iterable<mixed> $entityInstances
     * @param class-string    $entityFqcn
     */
    public function load(iterable $entityInstances, string $entityFqcn, FieldCollection $fields, string $pageName): void
    {
        // non-rewindable iterators (e.g. generators) can't be iterated here and again when rendering the page
        if (!\is_array($entityInstances) && !$entityInstances instanceof \ArrayIterator) {
            return;
        }

        $entityManager = $this->doctrine->getManagerForClass($entityFqcn);
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $classMetadata = $entityManager->getClassMetadata($entityFqcn);
        $propertyNames = $this->findToOneProperties($fields, $classMetadata, $entityManager, $pageName);
        if ([] === $propertyNames) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();
        /** @var array<class-string, array<int, mixed>> $identifiersByRootEntity root entity FQCN => proxy object ID => entity ID */
        $identifiersByRootEntity = [];
        foreach ($entityInstances as $entityInstance) {
            if (!$entityInstance instanceof $entityFqcn) {
                continue;
            }

            foreach ($propertyNames as $propertyName => $rootEntityFqcn) {
                // the value is read with the Doctrine metadata instead of the getters of the
                // entity, because getters can run their own queries
                $associatedEntity = $classMetadata->getFieldValue($entityInstance, $propertyName);
                if (null === $associatedEntity || !$unitOfWork->isUninitializedObject($associatedEntity)) {
                    continue;
                }

                // the identity map holds a single proxy per entity, so the proxy object ID removes duplicated IDs
                $identifier = $unitOfWork->getEntityIdentifier($associatedEntity);
                $identifiersByRootEntity[$rootEntityFqcn][spl_object_id($associatedEntity)] = reset($identifier);
            }
        }

        $batchSize = $entityManager->getConfiguration()->getEagerFetchBatchSize();
        foreach ($identifiersByRootEntity as $rootEntityFqcn => $identifierValues) {
            $identifierName = $entityManager->getClassMetadata($rootEntityFqcn)->getSingleIdentifierFieldName();

            // the entity persister is used instead of the repository to load the entities the same way
            // as Doctrine's eager fetch mode: it initializes the proxies already present in the identity
            // map and it joins the inverse side of one-to-one associations instead of querying them per row
            foreach (array_chunk(array_values($identifierValues), $batchSize) as $batchOfIdentifierValues) {
                $unitOfWork->getEntityPersister($rootEntityFqcn)->loadAll([$identifierName => $batchOfIdentifierValues]);
            }
        }
    }

    /**
     * The decision is based on the Doctrine metadata of the property and not on the field
     * type because any field (e.g. TextField) displaying a to-one association property loads
     * the associated entity when rendering it.
     *
     * @param ClassMetadata<object> $classMetadata
     *
     * @return array<string, class-string> property name => root entity FQCN of the associated entity
     */
    private function findToOneProperties(FieldCollection $fields, ClassMetadata $classMetadata, EntityManagerInterface $entityManager, string $pageName): array
    {
        $propertyNames = [];
        foreach ($fields as $field) {
            if (!$field->isDisplayedOn($pageName)) {
                continue;
            }

            // for nested properties (e.g. 'author.company') only the first association is loaded,
            // because it's the only one whose associated entities are known after the page query
            $propertyName = explode('.', $field->getProperty(), 2)[0];
            if (!$classMetadata->hasAssociation($propertyName) || !$classMetadata->isSingleValuedAssociation($propertyName)) {
                continue;
            }

            // entities with a composite ID or whose ID is an association (derived identity) can't be loaded by a list of IDs
            $targetClassMetadata = $entityManager->getClassMetadata($classMetadata->getAssociationTargetClass($propertyName));
            if ($targetClassMetadata->isIdentifierComposite || $targetClassMetadata->hasAssociation($targetClassMetadata->getSingleIdentifierFieldName())) {
                continue;
            }

            $propertyNames[$propertyName] = $targetClassMetadata->rootEntityName;
        }

        return $propertyNames;
    }
}
