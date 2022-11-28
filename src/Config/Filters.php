<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Filters
{
    private FilterConfigDto $dto;

    private function __construct(FilterConfigDto $filterConfigDto)
    {
        $this->dto = $filterConfigDto;
    }

    public static function new(): self
    {
        $dto = new FilterConfigDto();

        return new self($dto);
    }

    public function add(FilterInterface|string $propertyNameOrFilter): self
    {
        $filterPropertyName = \is_string($propertyNameOrFilter) ? $propertyNameOrFilter : (string) $propertyNameOrFilter;
        if (null !== $this->dto->getFilter($filterPropertyName)) {
            throw new \InvalidArgumentException(sprintf('There are two or more different filters defined for the "%s" property, but you can only define a single filter per property.', $filterPropertyName));
        }

        $this->dto->addFilter($propertyNameOrFilter);

        return $this;
    }

    public function getAsDto(): FilterConfigDto
    {
        return $this->dto;
    }
}
