<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

interface EntityUpdaterInterface
{
    public function updateProperty(EntityDto $entityDto, string $propertyName, $value): void;
}
