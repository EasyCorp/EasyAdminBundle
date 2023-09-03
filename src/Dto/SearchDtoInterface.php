<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use Symfony\Component\HttpFoundation\Request;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface SearchDtoInterface
{
    public function getRequest(): Request;

    public function getSort(): array;

    public function isSortingField(string $fieldProperty): bool;

    public function getSortDirection(string $fieldProperty): string;

    public function getQuery(): string;

    /**
     * Splits the query search string into a set of terms to search, taking into
     * account that quoted strings must be considered as a single term.
     * For example:
     *  'foo bar' => ['foo', 'bar']
     *  'foo "bar baz" qux' => ['foo', 'bar baz', 'qux'].
     */
    public function getQueryTerms(): array;

    /**
     * @return string[]|null
     */
    public function getSearchableProperties(): ?array;

    public function getAppliedFilters(): ?array;
}
