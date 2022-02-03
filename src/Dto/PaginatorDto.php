<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class PaginatorDto
{
    private ?int $pageNumber = null;
    private int $pageSize;
    private int $rangeSize;
    private int $rangeEdgeSize;
    private bool $fetchJoinCollection;
    private ?bool $useOutputWalkers;

    public function __construct(int $pageSize, int $rangeSize, int $rangeEdgeSize, bool $fetchJoinCollection, ?bool $useOutputWalkers)
    {
        $this->pageSize = $pageSize;
        $this->rangeSize = $rangeSize;
        $this->rangeEdgeSize = $rangeEdgeSize;
        $this->fetchJoinCollection = $fetchJoinCollection;
        $this->useOutputWalkers = $useOutputWalkers;
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
