<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection\CollectionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityCollection implements CollectionInterface
{
    /** @var EntityDto[] */
    private $entities;

    /**
     * @param EntityDto[] $entities
     */
    private function __construct(array $entities)
    {
        $this->entities = $entities;
    }

    /**
     * @param EntityDto[] $entities
     */
    public static function new(array $entities): self
    {
        return new self($entities);
    }

    public function get(string $entityId): ?EntityDto
    {
        return $this->entities[$entityId] ?? null;
    }

    public function set(EntityDto $newOrUpdatedEntity): void
    {
        $this->entities[$newOrUpdatedEntity->getPrimaryKeyValueAsString()] = $newOrUpdatedEntity;
    }

    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->entities);
    }

    public function offsetGet($offset)
    {
        return $this->entities[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->entities[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->entities[$offset]);
    }

    public function count(): int
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
}
