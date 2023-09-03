<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FilterConfigDtoInterface
{
    /**
     * @param FilterInterface|string $filterNameOrConfig
     */
    public function addFilter($filterNameOrConfig): void;

    /**
     * @return FilterInterface|string|null
     */
    public function getFilter(string $propertyName);

    public function all(): array;
}
