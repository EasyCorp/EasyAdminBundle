<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Field;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FieldInterface
{
    public static function new(string $propertyName, ?string $label = null);

    public function getAsDto(): FieldDto;
}
