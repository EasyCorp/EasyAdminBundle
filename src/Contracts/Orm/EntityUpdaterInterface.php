<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * Updates the value of a property of an entity.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityUpdaterInterface
{
    public function updateProperty(EntityDto $entityDto, string $propertyName, mixed $value): void;
}
