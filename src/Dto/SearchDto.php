<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
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
    private $appliedFilters;

    public function __construct(Request $request, ?array $searchableProperties, ?string $query, array $defaultSort, array $customSort, ?array $appliedFilters)
    {
        $this->request = $request;
        $this->searchableProperties = $searchableProperties;
        $this->query = trim($query);
        $this->defaultSort = $defaultSort;
        $this->customSort = $customSort;
        $this->appliedFilters = $appliedFilters;
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

    public function isSortingField(string $fieldProperty): bool
    {
        $firstSortField = \count($this->getSort()) > 0 ? array_keys($this->getSort())[0] : null;
        if (null === $firstSortField) {
            return false;
        }

        // TODO: check for association properties when they support search (e.g. 'user.name')
        return $fieldProperty === $firstSortField;
    }

    public function getSortDirection(string $fieldProperty): string
    {
        return \array_key_exists($fieldProperty, $this->getSort()) ? $this->getSort()[$fieldProperty] : 'DESC';
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * @return string[]|null
     */
    public function getSearchableProperties(): ?array
    {
        return $this->searchableProperties;
    }

    public function getAppliedFilters(): ?array
    {
        return $this->appliedFilters;
    }
}
