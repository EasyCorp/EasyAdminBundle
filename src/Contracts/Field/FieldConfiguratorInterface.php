<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Field;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FieldConfiguratorInterface
{
    public function supports(FieldDtoInterface $field, EntityDtoInterface $entityDto): bool;

    public function configure(FieldDtoInterface $field, EntityDtoInterface $entityDto, AdminContext $context): void;
}
