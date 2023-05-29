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

    public function getPageRange(?int $pagesOnEachSide = null, ?int $pagesOnEdges = null): iterable;

    public function getPageSize(): int;

    public function hasPreviousPage(): bool;

    public function getPreviousPage(): int;

    public function hasNextPage(): bool;

    public function getNextPage(): int;

    public function hasToPaginate(): bool;

    public function isOutOfRange(): bool;

    public function getNumResults(): int;

    public function getResults(): ?iterable;

    public function getResultsAsJson(): string;
}
