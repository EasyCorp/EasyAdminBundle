<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FilterInterface extends \Stringable
{
    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void;

    public function getAsDto(): FilterDto;
}
