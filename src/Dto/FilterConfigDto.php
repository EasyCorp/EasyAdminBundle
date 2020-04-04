<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterConfigDto
{
    /** @var KeyValueStore */
    private $filters;

    public function __construct()
    {
        $this->filters = KeyValueStore::new();
    }

    /**
     * @param FilterInterface|string $filterNameOrConfig
     */
    public function addFilter($filterNameOrConfig): void
    {
        $this->filters->set((string) $filterNameOrConfig, $filterNameOrConfig);
    }

    /**
     * @return FilterInterface|string|null
     */
    public function getFilter(string $propertyName)
    {
        return $this->filters->get($propertyName);
    }

    public function all(): array
    {
        return $this->filters->all();
    }
}
