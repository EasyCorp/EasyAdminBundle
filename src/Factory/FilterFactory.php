<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FiltersDto;

final class FilterFactory
{
    public function create(FiltersDto $filters, array $fields): FiltersDto
    {
        $builtFilters = [];
        foreach ($filters->getConfiguredFilters() as $property => $filter) {
            if (is_string($filter)) {
                dd($filter);
            }

            $builtFilters[$property] = $filter;
        }

        return $filters->updateConfiguredFilters($builtFilters);
    }
}
