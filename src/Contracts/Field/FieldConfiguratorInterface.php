<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Field;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

interface FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool;

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void;
}
