<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityPaginatorInterface
{
    public function paginate(PaginatorDto $paginatorDto, QueryBuilder $queryBuilder): self;

    public function generateUrlForPage(int $page): string;

    public function getCurrentPage(): int;

    public function getLastPage(): int;

    /**
     * @return iterable<int|null>
     */
    public function getPageRange(?int $pagesOnEachSide = null, ?int $pagesOnEdges = null): iterable;

    public function getPageSize(): int;

    public function hasPreviousPage(): bool;

    public function getPreviousPage(): int;

    public function hasNextPage(): bool;

    public function getNextPage(): int;

    public function hasToPaginate(): bool;

    public function isOutOfRange(): bool;

    public function getNumResults(): int;

    /**
     * @return iterable<mixed>|null
     */
    public function getResults(): ?iterable;

    /**
     * The signature of this method will add the commented parameters in EasyAdmin 5.0.0.
     */
    public function getResultsAsJson(/* ?callable $callback = null, ?string $twigTemplate = null, bool $renderAsHtml = false */): string;
}
