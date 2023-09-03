<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection\CollectionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityCollection implements CollectionInterface
{
    /**
     * @param EntityDtoInterface[] $entities
     */
    private function __construct(private array $entities)
    {
    }

    /**
     * @param EntityDtoInterface[] $entities
     */
    public static function new(array $entities): self
    {
        return new self($entities);
    }

    public function get(string $entityId): ?EntityDtoInterface
    {
        return $this->entities[$entityId] ?? null;
    }

    public function set(EntityDtoInterface $newOrUpdatedEntity): void
    {
        $this->entities[$newOrUpdatedEntity->getPrimaryKeyValueAsString()] = $newOrUpdatedEntity;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->entities);
    }

    public function offsetGet(mixed $offset): EntityDtoInterface
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
     * @return \ArrayIterator<EntityDtoInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->entities);
    }
}
