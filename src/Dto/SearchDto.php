<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class SearchDto
{
    /**
     * @internal
     *
     * @var array<string, 'ASC'|'DESC'>
     */
    private ?array $cachedSortConfig = null;
    private readonly string $query;

    /**
     * @param array<string>|null          $searchableProperties
     * @param array<string, 'ASC'|'DESC'> $defaultSort
     * @param array<string, 'ASC'|'DESC'> $customSort
     * @param array<string, mixed>|null   $appliedFilters
     */
    public function __construct(
        private readonly Request $request,
        private readonly ?array $searchableProperties,
        ?string $query,
        private readonly array $defaultSort,
        private readonly array $customSort,
        private readonly ?array $appliedFilters,
        private readonly string $searchMode = SearchMode::ALL_TERMS,
    ) {
        $this->query = trim((string) $query);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return array<string, 'ASC'|'DESC'>
     */
    public function getSort(): array
    {
        if (null !== $this->cachedSortConfig) {
            return $this->cachedSortConfig;
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

        return $this->cachedSortConfig = $mergedSort;
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

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Splits the query search string into a set of terms to search, taking into
     * account that quoted strings must be considered as a single term.
     * For example:
     *  'foo bar' => ['foo', 'bar']
     *  'foo "bar baz" qux' => ['foo', 'bar baz', 'qux'].
     *
     * @return array<string>
     */
    public function getQueryTerms(): array
    {
        preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $this->query, $matches);
        $terms = array_map(static fn ($match) => trim($match, '" '), $matches[0]);

        return $terms;
    }

    /**
     * @return string[]|null
     */
    public function getSearchableProperties(): ?array
    {
        return $this->searchableProperties;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAppliedFilters(): ?array
    {
        return $this->appliedFilters;
    }

    public function getSearchMode(): string
    {
        return $this->searchMode;
    }
}
