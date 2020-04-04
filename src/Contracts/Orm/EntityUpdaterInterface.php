<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityUpdaterInterface
{
    public function updateProperty(EntityDto $entityDto, string $propertyName, $value): void;
}
