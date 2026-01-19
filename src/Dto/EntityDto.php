<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @template TEntity of object
 */
final class EntityDto implements \Stringable
{
    private bool $isAccessible = true;
    /** @var TEntity|null */
    private $instance;
    private mixed $primaryKeyValue = null;
    private ?FieldCollection $fields = null;
    private ?ActionCollection $actions = null;

    /**
     * @param class-string<TEntity>  $fqcn
     * @param ClassMetadata<TEntity> $metadata
     * @param TEntity|null           $entityInstance
     */
    public function __construct(private readonly string $fqcn, private readonly ClassMetadata $metadata, private readonly string|Expression|null $permission = null, /* ?object */ $entityInstance = null)
    {
        if (!\is_object($entityInstance)
            && null !== $entityInstance) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$entityInstance',
                __METHOD__,
                '"object" or "null"',
                \gettype($entityInstance)
            );
        }

        $this->instance = $entityInstance;
    }

    public function __toString(): string
    {
        if (null === $this->instance) {
            return '';
        }

        if ($this->instance instanceof \Stringable) {
            return (string) $this->instance;
        }

        return sprintf('%s #%s', $this->getName(), substr($this->getPrimaryKeyValueAsString(), 0, 16));
    }

    /**
     * @return class-string<TEntity>
     */
    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function getName(): string
    {
        return basename(str_replace('\\', '/', $this->fqcn));
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0, use $entityDto->__toString() instead
     */
    public function toString(): string
    {
        return $this->__toString();
    }

    /**
     * @return object|null
     *
     * @phpstan-return TEntity|null
     */
    public function getInstance()/* : ?object */
    {
        return $this->instance;
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0, use $entityDto->getClassMetadata()->getSingleIdentifierFieldName() instead
     */
    public function getPrimaryKeyName(): string
    {
        return $this->metadata->getSingleIdentifierFieldName();
    }

    public function getPrimaryKeyValue(): mixed
    {
        if (null === $this->instance) {
            return null;
        }

        if (null !== $this->primaryKeyValue) {
            return $this->primaryKeyValue;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        try {
            $primaryKeyValue = $propertyAccessor->getValue($this->instance, $this->metadata->getSingleIdentifierFieldName());
        } catch (UninitializedPropertyException $exception) {
            $primaryKeyValue = null;
        }

        return $this->primaryKeyValue = $primaryKeyValue;
    }

    public function getPrimaryKeyValueAsString(): string
    {
        return (string) $this->getPrimaryKeyValue();
    }

    public function getPermission(): string|Expression|null
    {
        return $this->permission;
    }

    public function isAccessible(): bool
    {
        return $this->isAccessible;
    }

    public function markAsInaccessible(): void
    {
        $this->isAccessible = false;
        $this->instance = null;
        $this->fields = null;
    }

    public function getFields(): ?FieldCollection
    {
        return $this->fields;
    }

    public function setFields(FieldCollection $fields): void
    {
        $this->fields = $fields;
    }

    public function setActions(ActionCollection $actions): void
    {
        $this->actions = $actions;
    }

    public function getActions(): ActionCollection
    {
        return $this->actions;
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->metadata;
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0, use $entityDto->getClassMetadata()->getFieldNames() instead
     *
     * Returns the names of all properties defined in the entity, no matter
     * if they are used or not in the application.
     *
     * @return array<string>
     */
    public function getAllPropertyNames(): array
    {
        return $this->metadata->getFieldNames();
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0, use $entityDto->getClassMetadata()->fieldMappings[$propertyName] and $entityDto->getClassMetadata()->associationMappings[$propertyName] instead
     */
    public function getPropertyMetadata(string $propertyName): KeyValueStore
    {
        if (isset($this->metadata->fieldMappings[$propertyName])) {
            /** @var FieldMapping|array $fieldMapping */
            /** @phpstan-ignore-next-line */
            $fieldMapping = $this->metadata->fieldMappings[$propertyName];

            // Doctrine ORM 2.x returns an array and Doctrine ORM 3.x returns a FieldMapping object
            if ($fieldMapping instanceof FieldMapping) {
                $fieldMapping = (array) $fieldMapping;
            }

            return KeyValueStore::new($fieldMapping);
        }

        if ($this->metadata->hasAssociation($propertyName)) {
            /** @var AssociationMapping|array $associationMapping */
            /** @phpstan-ignore-next-line */
            $associationMapping = $this->metadata->associationMappings[$propertyName];

            // Doctrine ORM 2.x returns an array and Doctrine ORM 3.x returns an AssociationMapping object
            if ($associationMapping instanceof AssociationMapping) {
                // Doctrine ORM 3.x doesn't include the 'type' key that tells the type of association
                // recreate that key to keep the code compatible with both versions
                $associationType = $associationMapping->type();

                $associationMapping = (array) $associationMapping;
                $associationMapping['type'] = $associationType;
            }

            return KeyValueStore::new($associationMapping);
        }

        throw new \InvalidArgumentException(sprintf('The "%s" field does not exist in the "%s" entity.', $propertyName, $this->getFqcn()));
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0, use $entityDto->getClassMetadata()->getFieldMapping($propertyName)->type and $entityDto->getClassMetadata()->getAssociationMapping($propertyName)->type() instead
     */
    public function getPropertyDataType(string $propertyName): string|int
    {
        if (isset($this->getClassMetadata()->fieldMappings[$propertyName])) {
            return $this->getClassMetadata()->fieldMappings[$propertyName]['type'];
        }
        if (isset($this->getClassMetadata()->associationMappings[$propertyName])) {
            return $this->getClassMetadata()->associationMappings[$propertyName]['type'];
        }
        throw new \InvalidArgumentException(sprintf('The "%s" field does not exist in the "%s" entity.', $propertyName, $this->getFqcn()));
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0, use isset($entityDto->getClassMetadata()->fieldMappings[$propertyName]) || $entityDto->getClassMetadata()->hasAssociation($propertyName) instead
     */
    public function hasProperty(string $propertyName): bool
    {
        return isset($this->metadata->fieldMappings[$propertyName])
            || $this->metadata->hasAssociation($propertyName);
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0 without replacement
     */
    public function isAssociation(string $propertyName): bool
    {
        if ($this->metadata->hasAssociation($propertyName)) {
            return true;
        }

        if (!str_contains($propertyName, '.')) {
            return false;
        }

        $propertyNameParts = explode('.', $propertyName, 2);

        return !isset($this->metadata->embeddedClasses[$propertyNameParts[0]]);
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0, use $entityDto->getClassMetadata()->isSingleValuedAssociation($propertyName)
     */
    public function isToOneAssociation(string $propertyName): bool
    {
        return $this->getClassMetadata()->isSingleValuedAssociation($propertyName);
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0, use $entityDto->getClassMetadata()->isCollectionValuedAssociation($propertyName)
     */
    public function isToManyAssociation(string $propertyName): bool
    {
        return $this->getClassMetadata()->isCollectionValuedAssociation($propertyName);
    }

    /**
     * @deprecated since 4.27 and to be removed in 5.0 without replacement
     */
    public function isEmbeddedClassProperty(string $propertyName): bool
    {
        $propertyNameParts = explode('.', $propertyName, 2);

        return isset($this->metadata->embeddedClasses[$propertyNameParts[0]]);
    }

    /**
     * @param TEntity|null $newEntityInstance
     */
    public function setInstance(?object $newEntityInstance): void
    {
        if (null !== $this->instance && null !== $newEntityInstance && !$newEntityInstance instanceof $this->fqcn) {
            throw new \InvalidArgumentException(sprintf('The new entity instance must be of the same type as the previous instance (original instance: "%s", new instance: "%s").', $this->fqcn, $newEntityInstance::class));
        }

        $this->instance = $newEntityInstance;
        $this->primaryKeyValue = null;
    }

    /**
     * @param TEntity $newEntityInstance
     */
    public function newWithInstance(/* object */ $newEntityInstance): self
    {
        if (!\is_object($newEntityInstance)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$newEntityInstance',
                __METHOD__,
                '"object"',
                \gettype($newEntityInstance)
            );
        }

        if (null !== $this->instance && !$newEntityInstance instanceof $this->fqcn) {
            throw new \InvalidArgumentException(sprintf('The new entity instance must be of the same type as the previous instance (original instance: "%s", new instance: "%s").', $this->fqcn, $newEntityInstance::class));
        }

        return new self($this->fqcn, $this->metadata, $this->permission, $newEntityInstance);
    }
}
