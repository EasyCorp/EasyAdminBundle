<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

final class EntityDto
{
    private $fqcn;
    private $metadata;
    private $instance;
    private $primaryKeyName;
    private $primaryKeyValue;
    private $requiredPermission;
    private $userHasPermission;
    /** @var ?FieldDtoCollection */
    private $fields;
    /** @var ?ActionDto[] */
    private $actions;

    public function __construct(string $entityFqcn, ClassMetadata $entityMetadata, ?string $entityPermission = null, $entityInstance = null)
    {
        $this->fqcn = $entityFqcn;
        $this->metadata = $entityMetadata;
        $this->instance = $entityInstance;
        $this->primaryKeyName = $this->metadata->getIdentifierFieldNames()[0];
        $this->requiredPermission = $entityPermission;
        $this->userHasPermission = true;
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

    public function getInstance()
    {
        return $this->instance;
    }

    public function getPrimaryKeyName(): ?string
    {
        return $this->primaryKeyName;
    }

    public function getPrimaryKeyValue()
    {
        if (null === $this->instance) {
            return null;
        }

        if (null !== $this->primaryKeyValue) {
            return $this->primaryKeyValue;
        }

        try {
            $r = ClassUtils::newReflectionObject($this->instance);
            $primaryKeyProperty = $r->getProperty($this->primaryKeyName);
            $primaryKeyProperty->setAccessible(true);
            $primaryKeyValue = $primaryKeyProperty->getValue($this->instance);
        } catch (\Exception $e) {
            $primaryKeyValue = null;
        }

        return $this->primaryKeyValue = $primaryKeyValue;
    }

    public function getPrimaryKeyValueAsString(): string
    {
        return (string) $this->getPrimaryKeyValue();
    }

    public function getPermission(): ?string
    {
        return $this->requiredPermission;
    }

    public function isAccessible(): bool
    {
        return true === $this->userHasPermission;
    }

    public function markAsInaccessible(): void
    {
        $this->instance = null;
        $this->fields = FieldDtoCollection::new([]);
        $this->userHasPermission = false;
    }

    public function getFields(): ?FieldDtoCollection
    {
        return $this->fields;
    }

    /**
     * @return ActionDto[]
     */
    public function getActions(): array
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

    public function getPropertyMetadata(string $propertyName): array
    {
        if (null === $this->metadata) {
            return [];
        }

        if (\array_key_exists($propertyName, $this->metadata->fieldMappings)) {
            return $this->metadata->fieldMappings[$propertyName];
        }

        if (\array_key_exists($propertyName, $this->metadata->associationMappings)) {
            return $this->metadata->associationMappings[$propertyName];
        }

        throw new \InvalidArgumentException(sprintf('The "%s" field does not exist in the "%s" entity.', $propertyName, $this->getFqcn()));
    }

    public function getPropertyDataType(string $propertyName)
    {
        return $this->getPropertyMetadata($propertyName)['type'];
    }

    public function hasProperty(string $propertyName): bool
    {
        return \array_key_exists($propertyName, $this->metadata->fieldMappings)
            || \array_key_exists($propertyName, $this->metadata->associationMappings);
    }

    public function isAssociation(string $propertyName): bool
    {
        return \array_key_exists($propertyName, $this->metadata->associationMappings)
            || (false !== strpos($propertyName, '.') && !$this->isEmbeddedClassProperty($propertyName));
    }

    public function isToOneAssociation(string $propertyName): bool
    {
        $associationType = $this->getPropertyMetadata($propertyName)['type'];

        return \in_array($associationType, [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE], true);
    }

    public function isToManyAssociation(string $propertyName): bool
    {
        $associationType = $this->getPropertyMetadata($propertyName)['type'];

        return \in_array($associationType, [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY], true);
    }

    public function isEmbeddedClassProperty(string $propertyName): bool
    {
        $propertyNameParts = explode('.', $propertyName, 2);

        return \array_key_exists($propertyNameParts[0], $this->metadata->embeddedClasses);
    }

    public function updateInstance($newEntityInstance): self
    {
        if (null !== $this->instance && !$newEntityInstance instanceof $this->fqcn) {
            throw new \InvalidArgumentException(sprintf('The new entity instance must be of the same type as the previous instance (original instance: "%s", new instance: "%s").', $this->fqcn, \get_class($newEntityInstance)));
        }

        $clone = clone $this;
        $clone->instance = $newEntityInstance;
        $clone->primaryKeyValue = null;

        return $clone;
    }

    public function updateFields(FieldDtoCollection $fields): self
    {
        $clone = clone $this;
        $clone->fields = $fields;

        return $clone;
    }

    public function updateActions(array $actions): self
    {
        $clone = clone $this;
        $clone->actions = $actions;

        return $clone;
    }
}
