<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\FilterInterface;

final class Filters
{
    /** @var string[]|FilterInterface[] */
    private $filters;

    private function __construct()
    {
        $this->filters = [];
    }

    public static function new(): self
    {
        return new self();
    }

    public function add($propertyNameOrFilter): self
    {
        if (!\is_string($propertyNameOrFilter) && !$propertyNameOrFilter instanceof FilterInterface) {
            throw new \InvalidArgumentException(sprintf('The argument of "%s" can only be either a string with the filter property name or an object implementing "%s".', __METHOD__, FilterInterface::class));
        }

        $filterPropertyName = \is_string($propertyNameOrFilter) ? $propertyNameOrFilter : (string) $propertyNameOrFilter;
        if (\array_key_exists($filterPropertyName, $this->filters)) {
            throw new \InvalidArgumentException(sprintf('There are two or more different filters defined for the "%s" property, but you can only define a single filter per property.', $filterPropertyName));
        }

        $this->filters[$filterPropertyName] = $propertyNameOrFilter;

        return $this;
    }

    public function all(): array
    {
        return $this->filters;
    }
}
