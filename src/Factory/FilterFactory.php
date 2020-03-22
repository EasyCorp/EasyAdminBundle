<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Fields;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FiltersDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTypeGuesser;

final class FilterFactory
{
    private $filterTypeGuesser;

    public function __construct(FilterTypeGuesser $filterTypeGuesser)
    {
        $this->filterTypeGuesser = $filterTypeGuesser;
    }

    public function create(FiltersDto $filters, Fields $fields, EntityDto $entityDto): FiltersDto
    {
        $builtFilters = [];
        foreach ($filters->getConfiguredFilters() as $property => $propertyNameOrFilter) {
            if (is_string($propertyNameOrFilter)) {
                $guessedFilter = $this->filterTypeGuesser->guessType($entityDto->getFqcn(), $propertyNameOrFilter);
                $filterFqcn = $guessedFilter->getType();
                $filterFormTypeOptions = $guessedFilter->getOptions();
                $filter = $filterFqcn::new($propertyNameOrFilter)->setFormTypeOptions($filterFormTypeOptions);
            } else {
                $filter = $propertyNameOrFilter;
            }

            $builtFilters[$property] = $filter;
        }

        return $filters->updateConfiguredFilters($builtFilters);
    }
}
