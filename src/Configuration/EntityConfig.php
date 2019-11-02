<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

final class EntityConfig
{
    private $entityMetadata;
    private $entityId;

    public function __construct(ClassMetadata $entityMetadata, $entityId)
    {
        $this->entityMetadata = $entityMetadata;
        $this->entityId = $entityId;
    }

    public function getId()
    {
        return $this->entityId;
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
