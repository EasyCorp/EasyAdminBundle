<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FilterInterface extends \Stringable
{
    public function apply(QueryBuilder $queryBuilder, FilterDataDtoInterface $filterDataDto, ?FieldDtoInterface $fieldDto, EntityDtoInterface $entityDto): void;

    public function getAsDto(): FilterDtoInterface;
}
