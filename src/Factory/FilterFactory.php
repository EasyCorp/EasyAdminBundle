<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTypeGuesser;

final class FilterFactory
{
    private $filterTypeGuesser;

    public function __construct(FilterTypeGuesser $filterTypeGuesser)
    {
        $this->filterTypeGuesser = $filterTypeGuesser;
    }

    public function create(array $filters, FieldCollection $fields, EntityDto $entityDto): array
    {
        $builtFilters = [];
        foreach ($filters as $property => $propertyNameOrFilter) {
            if (\is_string($propertyNameOrFilter)) {
                $guessedFilter = $this->filterTypeGuesser->guessType($entityDto->getFqcn(), $propertyNameOrFilter);
                $filterFqcn = $guessedFilter->getType();
                $filterFormTypeOptions = $guessedFilter->getOptions();
                $filter = $filterFqcn::new($propertyNameOrFilter)->setFormTypeOptions($filterFormTypeOptions);
            } else {
                $filter = $propertyNameOrFilter;
            }

            $builtFilters[$property] = $filter;
        }

        return $builtFilters;
    }
}
