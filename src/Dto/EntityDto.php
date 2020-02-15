<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;

final class EntityDto
{
    private $fqcn;
    private $metadata;
    private $instance;
    private $idName;
    private $idValue;
    private $requiredPermission;
    private $userHasPermission;
    /** @var ?PropertyDtoCollection */
    private $properties;
    /** @var ?ActionDto[] */
    private $actions;

    public function __construct(string $entityFqcn, ClassMetadata $entityMetadata, ?string $entityPermission = null, $entityInstance = null)
    {
        $this->fqcn = $entityFqcn;
        $this->metadata = $entityMetadata;
        $this->instance = $entityInstance;
        $this->idName = $this->metadata->getIdentifierFieldNames()[0];
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

    public function getInstance()
    {
        return $this->instance;
    }

    public function getIdName(): ?string
    {
        return $this->idName;
    }

    public function getIdValue()
    {
        if (null === $this->instance) {
            return null;
        }

        if (null !== $this->idValue) {
            return $this->idValue;
        }

        $r = new \ReflectionObject($this->instance);
        $idProperty = $r->getProperty($this->idName);
        $idProperty->setAccessible(true);
        $idValue = $idProperty->getValue($this->instance);

        return $this->idValue = $idValue;
    }

    public function getIdValueAsString(): string
    {
        return (string) $this->getIdValue();
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
        $this->properties = PropertyDtoCollection::new([]);
        $this->userHasPermission = false;
    }

    public function getProperties(): ?PropertyDtoCollection
    {
        return $this->properties;
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
        if (!\array_key_exists($propertyName, $this->metadata->fieldMappings)) {
            throw new \InvalidArgumentException(sprintf('The "%s" property does not exist in the "%s" entity.', $propertyName, $this->getFqcn()));
        }

        return $this->metadata->fieldMappings[$propertyName];
    }

    public function getPropertyDataType(string $propertyName)
    {
        return $this->getPropertyMetadata($propertyName)['type'];
    }

    public function hasProperty(string $propertyName): bool
    {
        return \array_key_exists($propertyName, $this->metadata->fieldMappings);
    }

    public function isAssociationProperty(string $propertyName): bool
    {
        return false !== strpos($propertyName, '.') && !$this->isEmbeddedClassProperty($propertyName);
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
        $clone->idValue = null;

        return $clone;
    }

    public function updateProperties(PropertyDtoCollection $properties): self
    {
        $clone = clone $this;
        $clone->properties = $properties;

        return $clone;
    }

    public function updateActions(array $actions): self
    {
        $clone = clone $this;
        $clone->actions = $actions;

        return $clone;
    }

    public function getDefaultProperties(string $action)
    {
        $defaultPropertyNames = [];
        $maxNumProperties = 'index' === $action ? 7 : \PHP_INT_MAX;

        $excludedPropertyTypes = [
            'edit' => ['binary', 'blob', 'json_array', 'json', 'object'],
            'index' => ['array', 'association', 'binary', 'blob', 'guid', 'json_array', 'json', 'object', 'simple_array', 'text'],
            'new' => ['binary', 'blob', 'json_array', 'json', 'object'],
            'detail' => [],
        ];

        $excludedPropertyNames = [
            'edit' => [$this->getIdName()],
            'index' => ['password', 'salt', 'slug', 'updatedAt', 'uuid'],
            'new' => [$this->getIdName()],
            'detail' => [],
        ];

        foreach ($this->getAllPropertyNames() as $propertyName) {
            $metadata = $this->getPropertyMetadata($propertyName);
            if (!\in_array($propertyName, $excludedPropertyNames[$action], true) && !\in_array($metadata['type'], $excludedPropertyTypes[$action], true)) {
                $defaultPropertyNames[] = $propertyName;
            }
        }

        if (\count($defaultPropertyNames) > $maxNumProperties) {
            $defaultPropertyNames = \array_slice($defaultPropertyNames, 0, $maxNumProperties, true);
        }

        return $defaultPropertyNames;
    }
}
