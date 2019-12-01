<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

final class EntityDto
{
    use PropertyModifierTrait;

    private $entityFqcn;
    private $entityMetadata;
    private $entityInstance;
    private $entityIdName;
    private $entityIdValue;

    public function __construct(string $entityFqcn, ClassMetadata $entityMetadata = null, $entityInstance = null, $entityIdValue = null)
    {
        $this->entityFqcn = $entityFqcn;
        $this->entityMetadata = $entityMetadata;
        $this->entityInstance = $entityInstance;
        $this->entityIdName = null === $this->entityMetadata ? null : $this->entityMetadata->getIdentifierFieldNames()[0];
        $this->entityIdValue = $entityIdValue;
    }

    public function getFqcn(): string
    {
        return $this->entityFqcn;
    }

    public function getShortClassName(): string
    {
        return basename(str_replace('\\', '/', $this->entityFqcn));
    }

    public function getInstance()
    {
        return $this->entityInstance;
    }

    public function getIdName(): ?string
    {
        return $this->entityIdName;
    }

    public function getIdValue()
    {
        return $this->entityIdValue;
    }

    public function getIdValueAsString(): string
    {
        return (string) $this->entityIdValue;
    }

    public function getPropertyMetadata(string $propertyName): array
    {
        if (!array_key_exists($propertyName, $this->entityMetadata->fieldMappings)) {
            throw new \InvalidArgumentException(sprintf('The "%s" property does not exist in the "%s" entity.', $propertyName, $this->getFqcn()));
        }

        return $this->entityMetadata->fieldMappings[$propertyName];
    }

    public function getDataType(string $propertyName)
    {
        return $this->getPropertyMetadata($propertyName)['type'];
    }

    public function hasProperty(string $propertyName): bool
    {
        return array_key_exists($propertyName, array_keys($this->entityMetadata->fieldMappings));
    }

    public function isAssociation(string $propertyName): bool
    {
        return false !== strpos($propertyName, '.') && !$this->isEmbeddedClass($propertyName);
    }

    public function isEmbeddedClass(string $propertyName): bool
    {
        $propertyNameParts = explode('.', $propertyName, 2);

        return \array_key_exists($propertyNameParts[0], $this->entityMetadata->embeddedClasses);
    }
}
