<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Counts the elements of the to-many associations displayed for a collection of entities
 * (e.g. the rows of an index page) with a single grouped COUNT query per association, instead
 * of loading the entire collection of each entity to display the number of related elements.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal
 */
final class ToManyAssociationCounter
{
    /** @var array<class-string, array<string, array<string, int>>> entity FQCN => property name => entity ID => number of elements */
    private array $counts = [];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    public function reset(): void
    {
        $this->counts = [];
    }

    public function preload(EntityCollection $entityDtos, FieldCollection $fields, string $pageName): void
    {
        $firstEntityDto = null;
        foreach ($entityDtos as $entityDto) {
            if (null !== $entityDto->getInstance()) {
                $firstEntityDto = $entityDto;
                break;
            }
        }

        if (null === $firstEntityDto) {
            return;
        }

        $entityFqcn = $firstEntityDto->getFqcn();
        $classMetadata = $firstEntityDto->getClassMetadata();
        $identifierName = $classMetadata->getSingleIdentifierFieldName();
        // selecting an association (e.g. a derived identity) is not valid DQL
        if ($classMetadata->hasAssociation($identifierName)) {
            return;
        }

        $entityManager = $this->getEntityManager($entityFqcn);
        $propertyNames = $this->findCountableProperties($fields, $classMetadata, $entityManager, $pageName);
        if ([] === $propertyNames) {
            return;
        }

        $connection = $entityManager->getConnection();
        $identifierType = $classMetadata->getTypeOfField($identifierName) ?? Types::STRING;
        $parameterType = match (Type::getType($identifierType)->getBindingType()) {
            ParameterType::INTEGER => ArrayParameterType::INTEGER,
            ParameterType::ASCII => ArrayParameterType::ASCII,
            ParameterType::BINARY => ArrayParameterType::BINARY,
            default => ArrayParameterType::STRING,
        };

        foreach ($propertyNames as $propertyName) {
            // the IDs are converted explicitly because Doctrine infers the type of array parameters
            // from their PHP type, which turns objects such as UUIDs into strings that don't match
            // the value stored in the database
            $identifierValues = [];
            foreach ($entityDtos as $entityDto) {
                $entityInstance = $entityDto->getInstance();
                if (null !== $entityInstance && $this->canUsePreloadedCount($entityInstance, $propertyName, $this->readCollection($entityInstance, $propertyName))) {
                    $identifierValues[] = $connection->convertToDatabaseValue($entityDto->getPrimaryKeyValue(), $identifierType);
                }
            }

            if ([] === $identifierValues) {
                continue;
            }

            $dql = sprintf(
                'SELECT entity.%1$s AS entityId, COUNT(associated) AS numElements FROM %2$s entity LEFT JOIN entity.%3$s associated WHERE entity.%1$s IN (:ids) GROUP BY entity.%1$s',
                $identifierName,
                $entityFqcn,
                $propertyName,
            );

            $rows = $entityManager->createQuery($dql)
                ->setParameter('ids', $identifierValues, $parameterType)
                ->getScalarResult();

            foreach ($rows as $row) {
                $this->counts[$entityFqcn][$propertyName][(string) $row['entityId']] = (int) $row['numElements'];
            }
        }
    }

    /**
     * Returns the preloaded number of elements of the given collection, which must be the
     * persistent collection of the given property of the entity, or null if the collection
     * must be counted directly (e.g. because it's already loaded, because it contains changes
     * not yet flushed or because it belongs to another entity or association).
     */
    public function getCount(EntityDto $entityDto, string $propertyName, mixed $collection): ?int
    {
        $entityInstance = $entityDto->getInstance();
        if (null === $entityInstance || !$this->canUsePreloadedCount($entityInstance, $propertyName, $collection)) {
            return null;
        }

        return $this->counts[$entityDto->getFqcn()][$propertyName][$entityDto->getPrimaryKeyValueAsString()] ?? null;
    }

    /**
     * @param ClassMetadata<object> $classMetadata
     *
     * @return list<string>
     */
    private function findCountableProperties(FieldCollection $fields, ClassMetadata $classMetadata, EntityManagerInterface $entityManager, string $pageName): array
    {
        $propertyNames = [];
        foreach ($fields as $field) {
            $propertyName = $field->getProperty();

            if (str_contains($propertyName, '.') || !$field->isDisplayedOn($pageName)) {
                continue;
            }

            if (!$classMetadata->hasAssociation($propertyName) || !$classMetadata->isCollectionValuedAssociation($propertyName)) {
                continue;
            }

            // FieldFactory turns a generic field of a to-many association with orphan removal
            // into a CollectionField, which iterates the collection instead of counting it
            $isCountedAssociationField = match ($field->getFieldFqcn()) {
                AssociationField::class => true,
                Field::class => true !== $classMetadata->getAssociationMapping($propertyName)['orphanRemoval'],
                default => false,
            };
            if (!$isCountedAssociationField) {
                continue;
            }

            if ($entityManager->getClassMetadata($classMetadata->getAssociationTargetClass($propertyName))->isIdentifierComposite) {
                continue;
            }

            $propertyNames[$propertyName] = $propertyName;
        }

        return array_values($propertyNames);
    }

    private function readCollection(object $entityInstance, string $propertyName): mixed
    {
        if (!$this->propertyAccessor->isReadable($entityInstance, $propertyName)) {
            return null;
        }

        return $this->propertyAccessor->getValue($entityInstance, $propertyName);
    }

    /**
     * The value in memory takes precedence over the preloaded count, and a configurator
     * can replace the value of the field with a collection of another entity or association.
     */
    private function canUsePreloadedCount(object $entityInstance, string $propertyName, mixed $collection): bool
    {
        return $collection instanceof PersistentCollection
            && $collection->getOwner() === $entityInstance
            && $collection->getMapping()['fieldName'] === $propertyName
            && !$collection->isInitialized()
            && !$collection->isDirty();
    }

    /**
     * @param class-string $entityFqcn
     */
    private function getEntityManager(string $entityFqcn): EntityManagerInterface
    {
        $entityManager = $this->doctrine->getManagerForClass($entityFqcn);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('There is no Doctrine Entity Manager defined for the "%s" class.', $entityFqcn));
        }

        return $entityManager;
    }
}
