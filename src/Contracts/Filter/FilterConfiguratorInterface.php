<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FilterConfiguratorInterface
{
    /**
     * @param EntityDto<object>    $entityDto
     * @param AdminContext<object> $context   This will change to AdminContextInterface in the next major version
     */
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool;

    /**
     * @param EntityDto<object>    $entityDto
     * @param AdminContext<object> $context   This will change to AdminContextInterface in the next major version
     */
    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void;
}
