<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;

final class EntityDto
{
    use PropertyModifierTrait;

    private $fqcn;
    private $metadata;
    private $propertiesDto;
    private $instance;
    private $idName;
    private $idValue;

    public function __construct(string $entityFqcn, ClassMetadata $entityMetadata, PropertyDtoCollection $propertiesDto, $entityInstance = null, $entityIdValue = null)
    {
        $this->fqcn = $entityFqcn;
        $this->metadata = $entityMetadata;
        $this->propertiesDto = $propertiesDto;
        $this->instance = $entityInstance;
        $this->idName = $this->metadata->getIdentifierFieldNames()[0];
        $this->idValue = $entityIdValue;
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function getName(): string
    {
        return basename(str_replace('\\', '/', $this->fqcn));
    }

    public function getProperties(): PropertyDtoCollection
    {
        return $this->propertiesDto;
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

    public function getPropertyMetadata(string $propertyName): array
    {
        if (!array_key_exists($propertyName, $this->metadata->fieldMappings)) {
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
        return array_key_exists($propertyName, array_keys($this->metadata->fieldMappings));
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
