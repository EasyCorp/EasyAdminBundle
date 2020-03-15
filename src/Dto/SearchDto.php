<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Symfony\Component\HttpFoundation\Request;

final class SearchDto
{
    private $request;
    private $defaultSort;
    private $customSort;
    /** @internal */
    private $mergedSort;
    private $query;
    /** @var string[]|null */
    private $searchableProperties;
    /** @var string[]|null */
    private $filters;

    public function __construct(Request $request, ?array $searchableProperties, ?string $query, array $defaultSort, array $customSort, ?array $filters)
    {
        $this->request = $request;
        $this->searchableProperties = $searchableProperties;
        $this->query = $query;
        $this->defaultSort = $defaultSort;
        $this->customSort = $customSort;
        $this->filters = $filters;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getSort(): array
    {
        if (null !== $this->mergedSort) {
            return $this->mergedSort;
        }

        // we can't use an array_merge() call because $customSort has more priority
        // than $defaultSort, so the default sort must only be applied if there's
        // not already a custom sort config for the same field
        $mergedSort = $this->customSort;
        foreach ($this->defaultSort as $fieldName => $order) {
            if (!\array_key_exists($fieldName, $mergedSort)) {
                $mergedSort[$fieldName] = $order;
            }
        }

        return $this->mergedSort = $mergedSort;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * @return string[]
     */
    public function getSearchableProperties(): array
    {
        return $this->searchableProperties;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }
}
