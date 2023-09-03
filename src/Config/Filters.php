<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDtoInterface;

final class Filters implements FiltersInterface
{
    private FilterConfigDto $dto;

    private function __construct(FilterConfigDtoInterface $filterConfigDto)
    {
        $this->dto = $filterConfigDto;
    }

    public static function new(): FiltersInterface
    {
        $dto = new FilterConfigDto();

        return new self($dto);
    }

    public function add(FilterInterface|string $propertyNameOrFilter): FiltersInterface
    {
        $filterPropertyName = \is_string($propertyNameOrFilter) ? $propertyNameOrFilter : (string) $propertyNameOrFilter;
        if (null !== $this->dto->getFilter($filterPropertyName)) {
            throw new \InvalidArgumentException(sprintf('There are two or more different filters defined for the "%s" property, but you can only define a single filter per property.', $filterPropertyName));
        }

        $this->dto->addFilter($propertyNameOrFilter);

        return $this;
    }

    public function getAsDto(): FilterConfigDtoInterface
    {
        return $this->dto;
    }
}
