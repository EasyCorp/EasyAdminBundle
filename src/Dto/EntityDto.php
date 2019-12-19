<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;

final class EntityDto
{
    use PropertyModifierTrait;

    private $fqcn;
    private $metadata;
    private $instance;
    private $idName;
    private $idValue;
    private $requiredPermission;
    private $userHasPermission;
    /** @var ?PropertyDtoCollection $properties */
    private $properties;

    public function __construct(string $entityFqcn, ClassMetadata $entityMetadata, ?string $entityPermission = null, $entityInstance = null, $entityIdValue = null)
    {
        $this->fqcn = $entityFqcn;
        $this->metadata = $entityMetadata;
        $this->instance = $entityInstance;
        $this->idName = $this->metadata->getIdentifierFieldNames()[0];
        $this->idValue = $entityIdValue;
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
        return $this->idValue;
    }

    public function getIdValueAsString(): string
    {
        return (string) $this->idValue;
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
        $this->propertiesDto = PropertyDtoCollection::new([]);
        $this->userHasPermission = false;
    }

    public function getProperties(): ?PropertyDtoCollection
    {
        return $this->properties;
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
}
