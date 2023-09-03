<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface PaginatorDtoInterface
{
    public function getPageNumber(): ?int;

    public function setPageNumber(int $pageNumber): void;

    public function getPageSize(): int;

    public function getRangeSize(): int;

    public function getRangeEdgeSize(): int;

    public function fetchJoinCollection(): bool;

    public function useOutputWalkers(): ?bool;
}
