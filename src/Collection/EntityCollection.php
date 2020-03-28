<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection\CollectionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

final class EntityCollection implements CollectionInterface
{
    /** @var EntityDto[] */
    private $entities;

    private function __construct(EntityDto $entityDto, iterable $entityInstances)
    {
        $this->entities = $this->processEntities($entityDto, $entityInstances);
    }

    public static function new(EntityDto $entityDto, iterable $entityInstances)
    {
        return new self($entityDto, $entityInstances);
    }

    public function get(string $entityId): ?EntityDto
    {
        return $this->entities[$entityId] ?? null;
    }

    public function set(EntityDto $newOrUpdatedEntity): void
    {
        $this->entities[$newOrUpdatedEntity->getPrimaryKeyValue()] = $newOrUpdatedEntity;
    }

    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->entities);
    }

    public function offsetGet($offset)
    {
        return $this->entities[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->entities[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->entities[$offset]);
    }

    public function count()
    {
        return \count($this->entities);
    }

    /**
     * @return \ArrayIterator|\Traversable|EntityDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->entities);
    }

    private function processEntities(EntityDto $entityDto, $entityInstances): array
    {
        $dtos = [];

        foreach ($entityInstances as $entityInstance) {
            $dto = $entityDto->newWithInstance($entityInstance);
            $dtos[$dto->getPrimaryKeyValue()] = $dto;
        }

        return $dtos;
    }
}
