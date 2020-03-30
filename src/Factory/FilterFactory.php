<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTypeGuesser;

final class FilterFactory
{
    private $filterTypeGuesser;

    public function __construct(FilterTypeGuesser $filterTypeGuesser)
    {
        $this->filterTypeGuesser = $filterTypeGuesser;
    }

    public function create(FilterConfigDto $filterConfig, FieldCollection $fields, EntityDto $entityDto): FilterCollection
    {
        $builtFilters = [];
        foreach ($filterConfig->all() as $property => $propertyNameOrFilter) {
            if (\is_string($propertyNameOrFilter)) {
                $propertyName = $propertyNameOrFilter;
                $guessedFilter = $this->filterTypeGuesser->guessType($entityDto->getFqcn(), $propertyName);
                $filterFqcn = $guessedFilter->getType();
                $filterFormTypeOptions = $guessedFilter->getOptions();
                $filter = $filterFqcn::new($propertyName)->setFormTypeOptions($filterFormTypeOptions);
            } else {
                $filter = $propertyNameOrFilter;
            }

            $builtFilters[$property] = $filter;
        }

        return FilterCollection::new($builtFilters);
    }
}
