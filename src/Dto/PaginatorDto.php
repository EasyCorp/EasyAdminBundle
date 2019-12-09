<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class PaginatorDto
{
    use PropertyModifierTrait;

    private $pageNumber;
    private $pageSize;
    private $fetchJoinCollection;
    private $useOutputWalkers;

    public function __construct(int $pageSize, bool $fetchJoinCollection, ?bool $useOutputWalkers)
    {
        $this->pageSize = $pageSize;
        $this->fetchJoinCollection = $fetchJoinCollection;
        $this->useOutputWalkers = $useOutputWalkers;
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
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
