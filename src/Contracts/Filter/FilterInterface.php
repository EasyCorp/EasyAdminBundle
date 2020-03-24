<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;

interface FilterInterface
{
    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto);

    public function getAsDto(): FilterDto;
}
