<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;

final class FilterConfigDto
{
    /** @var FilterDto[]|string[] */
    private $filters;

    public function __construct()
    {
    }

    /**
     * @param FilterInterface|string $filterNameOrConfig
     */
    public function addFilter($filterNameOrConfig): void
    {
        $this->filters[(string) $filterNameOrConfig] = $filterNameOrConfig;
    }

    /**
     * @return FilterInterface|string|null
     */
    public function getFilter(string $propertyName)
    {
        return $this->filters[$propertyName] ?? null;
    }

    public function all(): array
    {
        return $this->filters;
    }
}
