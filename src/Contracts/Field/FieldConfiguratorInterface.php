<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Field;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

interface FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool;

    public function configure(string $action, FieldInterface $field, EntityDto $entityDto): void;
}
