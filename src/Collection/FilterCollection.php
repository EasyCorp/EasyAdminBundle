<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection\CollectionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterCollection implements CollectionInterface
{
    /** @var FilterDto[] */
    private $filters;

    /**
     * @param FilterDto[] $filters
     */
    private function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param FilterDto[] $filters
     */
    public static function new(array $filters = []): self
    {
        return new self($filters);
    }

    /**
     * @return FilterDto[]
     */
    public function all(): array
    {
        return $this->filters;
    }

    public function get(string $filterName): ?FilterDto
    {
        return $this->filters[$filterName] ?? null;
    }

    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->filters);
    }

    public function offsetGet($offset)
    {
        return $this->filters[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->filters[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->filters[$offset]);
    }

    public function count(): int
    {
        return \count($this->filters);
    }

    /**
     * @return \ArrayIterator|\Traversable|FilterDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->filters);
    }
}
