<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection\CollectionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterCollection implements CollectionInterface
{
    /**
     * @param FilterDtoInterface[] $filters
     */
    private function __construct(private array $filters)
    {
    }

    /**
     * @param FilterDtoInterface[] $filters
     */
    public static function new(array $filters = []): self
    {
        return new self($filters);
    }

    /**
     * @return FilterDtoInterface[]
     */
    public function all(): array
    {
        return $this->filters;
    }

    public function get(string $filterName): ?FilterDtoInterface
    {
        return $this->filters[$filterName] ?? null;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->filters);
    }

    public function offsetGet(mixed $offset): FilterDtoInterface
    {
        return $this->filters[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->filters[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->filters[$offset]);
    }

    public function count(): int
    {
        return \count($this->filters);
    }

    /**
     * @return \ArrayIterator<FilterDto>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->filters);
    }
}
