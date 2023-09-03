<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityUpdaterInterface
{
    public function updateProperty(EntityDtoInterface $entityDto, string $propertyName, $value): void;
}
