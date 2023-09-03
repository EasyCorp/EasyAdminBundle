<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityRepositoryInterface
{
    public function createQueryBuilder(SearchDtoInterface $searchDto, EntityDtoInterface $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder;
}
