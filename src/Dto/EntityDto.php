<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

final class EntityDto
{
    private $entityMetadata;
    private $entityInstance;
    private $entityIdName;
    private $entityIdValue;

    public function __construct(ClassMetadata $entityMetadata, $entityInstance, $entityIdValue)
    {
        $this->entityMetadata = $entityMetadata;
        $this->entityInstance = $entityInstance;
        $this->entityIdName = $this->entityMetadata->getIdentifierFieldNames()[0];
        $this->entityIdValue = $entityIdValue;
    }

    public function getInstance()
    {
        return $this->entityInstance;
    }

    public function getIdName(): string
    {
        return $this->entityIdName;
    }

    public function getIdValue()
    {
        return $this->entityIdValue;
    }

    public function getShortClassName(): string
    {
        return basename(str_replace('\\', '/', $this->entityMetadata->getName()));
    }

    public function getPropertyMetadata(string $propertyName)
    {
        return $this->entityMetadata->fieldMappings[$propertyName];
    }

    public function hasProperty(string $propertyName): bool
    {
        return array_key_exists($propertyName, array_keys($this->entityMetadata->fieldMappings));
    }
}
