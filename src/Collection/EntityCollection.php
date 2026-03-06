<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @param array<string, EntityDto> $entities
     */
    public function __construct(private array $entities = [])
    {
    }

    public function get(string $entityId): ?EntityDto
    {
        return $this->entities[$entityId] ?? null;
    }

    public function set(EntityDto $newOrUpdatedEntity): void
    {
        $this->entities[$newOrUpdatedEntity->getPrimaryKeyValueAsString()] = $newOrUpdatedEntity;
    }

    public function first(): ?EntityDto
    {
        return $this->entities[array_key_first($this->entities) ?? ''] ?? null;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->entities);
    }

    public function offsetGet(mixed $offset): EntityDto
    {
        return $this->entities[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->entities[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->entities[$offset]);
    }

    public function count(): int
    {
        return \count($this->entities);
    }

    /**
     * @return \ArrayIterator<string, EntityDto>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->entities);
    }
}
