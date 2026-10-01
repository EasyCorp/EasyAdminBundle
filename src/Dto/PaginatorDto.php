<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class PaginatorDto
{
    private ?int $pageNumber = null;
    /** @var array<class-string, list<string>> */
    private array $eagerFetchedAssociations = [];

    public function __construct(
        private readonly int $pageSize,
        private readonly int $rangeSize,
        private readonly int $rangeEdgeSize,
        private readonly bool $fetchJoinCollection,
        private readonly ?bool $useOutputWalkers,
    ) {
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getRangeSize(): int
    {
        return $this->rangeSize;
    }

    public function getRangeEdgeSize(): int
    {
        return $this->rangeEdgeSize;
    }

    public function fetchJoinCollection(): bool
    {
        return $this->fetchJoinCollection;
    }

    public function useOutputWalkers(): ?bool
    {
        return $this->useOutputWalkers;
    }

    /**
     * Returns the to-one associations that Doctrine must load eagerly (in a single query
     * per associated entity) when hydrating the results of the paginated query.
     *
     * @return array<class-string, list<string>> entity FQCN => association property names
     *
     * @internal
     */
    public function getEagerFetchedAssociations(): array
    {
        return $this->eagerFetchedAssociations;
    }

    /**
     * @param array<class-string, list<string>> $eagerFetchedAssociations entity FQCN => association property names
     *
     * @internal
     */
    public function setEagerFetchedAssociations(array $eagerFetchedAssociations): void
    {
        $this->eagerFetchedAssociations = $eagerFetchedAssociations;
    }
}
