<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection\CollectionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * @template TInstance of object
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityCollection implements CollectionInterface
{
    /**
     * @param EntityDto<TInstance>[] $entities
     */
    private function __construct(private array $entities)
    {
    }

    /**
     * @param EntityDto<TInstance>[] $entities
     */
    public static function new(array $entities): self
    {
        return new self($entities);
    }

    /**
     * @return EntityDto<TInstance>|null
     */
    public function get(string $entityId): ?EntityDto
    {
        return $this->entities[$entityId] ?? null;
    }

    /**
     * @param EntityDto<TInstance> $newOrUpdatedEntity
     */
    public function set(EntityDto $newOrUpdatedEntity): void
    {
        $this->entities[$newOrUpdatedEntity->getPrimaryKeyValueAsString()] = $newOrUpdatedEntity;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->entities);
    }

    /**
     * @return EntityDto<TInstance>
     */
    public function offsetGet(mixed $offset): EntityDto
    {
        return $this->entities[$offset];
    }

    /**
     * @param EntityDto<TInstance> $value
     */
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
     * @return \ArrayIterator<EntityDto<TInstance>>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->entities);
    }
}
