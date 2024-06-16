<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterConfigDto
{
    private KeyValueStore $filters;

    public function __construct()
    {
        $this->filters = KeyValueStore::new();
    }

    /**
     * @param FilterInterface|string $filterNameOrConfig
     */
    public function addFilter($filterNameOrConfig): void
    {
        if (!\is_string($filterNameOrConfig) && !$filterNameOrConfig instanceof FilterInterface) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$filterNameOrConfig',
                __METHOD__,
                sprintf('"string" or "%s"', FilterInterface::class),
                \gettype($filterNameOrConfig)
            );
        }

        $this->filters->set((string) $filterNameOrConfig, $filterNameOrConfig);
    }

    /**
     * @return FilterInterface|string|null
     */
    public function getFilter(string $propertyName)/* : FilterInterface|string|null */
    {
        return $this->filters->get($propertyName);
    }

    public function all(): array
    {
        return $this->filters->all();
    }
}
