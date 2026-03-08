<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class PaginatorDto
{
    private ?int $pageNumber = null;

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
}
