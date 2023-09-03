<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FiltersInterface
{
    public function add(FilterInterface|string $propertyNameOrFilter): FiltersInterface;

    public function getAsDto(): FilterConfigDtoInterface;
}
