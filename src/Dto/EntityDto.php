<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityDto
{
    private bool $isAccessible = true;
    private string $fqcn;
    /** @var ClassMetadataInfo */
    private ClassMetadata $metadata;
    private $instance;
    private $primaryKeyName;
    private mixed $primaryKeyValue = null;
    private ?string $permission;
    private ?FieldCollection $fields = null;
    private ?ActionCollection $actions = null;

    /**
     * @param ClassMetadata&ClassMetadataInfo $entityMetadata
     */
    public function __construct(string $entityFqcn, ClassMetadata $entityMetadata, ?string $entityPermission = null, /* ?object */ $entityInstance = null)
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

        $this->fqcn = $entityFqcn;
        $this->metadata = $entityMetadata;
        $this->instance = $entityInstance;
        $this->primaryKeyName = $this->metadata->getIdentifierFieldNames()[0];
        $this->permission = $entityPermission;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function getName(): string
    {
        return basename(str_replace('\\', '/', $this->fqcn));
    }

    public function toString(): string
    {
        if (null === $this->instance) {
            return '';
        }

        if (method_exists($this->instance, '__toString')) {
            return (string) $this->instance;
        }

        return sprintf('%s #%s', $this->getName(), substr($this->getPrimaryKeyValueAsString(), 0, 16));
    }

    public function getInstance()/* : ?object */
    {
        return $this->instance;
    }

    public function getPrimaryKeyName(): ?string
    {
        return $this->primaryKeyName;
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

        $primaryKeyValue = $propertyAccessor->getValue($this->instance, $this->primaryKeyName);

        return $this->primaryKeyValue = $primaryKeyValue;
    }

    public function getPrimaryKeyValueAsString(): string
    {
        return (string) $this->getPrimaryKeyValue();
    }

    public function getPermission(): ?string
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

    /**
     * Returns the names of all properties defined in the entity, no matter
     * if they are used or not in the application.
     */
    public function getAllPropertyNames(): array
    {
        return $this->metadata->getFieldNames();
    }

    public function getPropertyMetadata(string $propertyName): KeyValueStore
    {
        if (\array_key_exists($propertyName, $this->metadata->fieldMappings)) {
            return KeyValueStore::new($this->metadata->fieldMappings[$propertyName]);
        }

        if (\array_key_exists($propertyName, $this->metadata->associationMappings)) {
            return KeyValueStore::new($this->metadata->associationMappings[$propertyName]);
        }

        throw new \InvalidArgumentException(sprintf('The "%s" field does not exist in the "%s" entity.', $propertyName, $this->getFqcn()));
    }

    public function getPropertyDataType(string $propertyName)
    {
        return $this->getPropertyMetadata($propertyName)->get('type');
    }

    public function hasProperty(string $propertyName): bool
    {
        return \array_key_exists($propertyName, $this->metadata->fieldMappings)
            || \array_key_exists($propertyName, $this->metadata->associationMappings);
    }

    public function isAssociation(string $propertyName): bool
    {
        return \array_key_exists($propertyName, $this->metadata->associationMappings)
            || (str_contains($propertyName, '.') && !$this->isEmbeddedClassProperty($propertyName));
    }

    public function isToOneAssociation(string $propertyName): bool
    {
        $associationType = $this->getPropertyMetadata($propertyName)->get('type');

        return \in_array($associationType, [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE], true);
    }

    public function isToManyAssociation(string $propertyName): bool
    {
        $associationType = $this->getPropertyMetadata($propertyName)->get('type');

        return \in_array($associationType, [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY], true);
    }

    public function isEmbeddedClassProperty(string $propertyName): bool
    {
        $propertyNameParts = explode('.', $propertyName, 2);

        return \array_key_exists($propertyNameParts[0], $this->metadata->embeddedClasses);
    }

    public function setInstance(?object $newEntityInstance): void
    {
        if (null !== $this->instance && null !== $newEntityInstance && !$newEntityInstance instanceof $this->fqcn) {
            throw new \InvalidArgumentException(sprintf('The new entity instance must be of the same type as the previous instance (original instance: "%s", new instance: "%s").', $this->fqcn, \get_class($newEntityInstance)));
        }

        $this->instance = $newEntityInstance;
        $this->primaryKeyValue = null;
    }

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
            throw new \InvalidArgumentException(sprintf('The new entity instance must be of the same type as the previous instance (original instance: "%s", new instance: "%s").', $this->fqcn, \get_class($newEntityInstance)));
        }

        return new self($this->fqcn, $this->metadata, $this->permission, $newEntityInstance);
    }
}
