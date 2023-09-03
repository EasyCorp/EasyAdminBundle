<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FilterConfiguratorInterface
{
    public function supports(
        FilterDtoInterface $filterDto,
        ?FieldDtoInterface $fieldDto,
        EntityDtoInterface $entityDto,
        AdminContext $context
    ): bool;

    public function configure(
        FilterDtoInterface $filterDto,
        ?FieldDtoInterface $fieldDto,
        EntityDtoInterface $entityDto,
        AdminContext $context
    ): void;
}
