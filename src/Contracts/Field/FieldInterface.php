<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Field;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;

interface FieldInterface
{
    public static function new(string $propertyName, ?string $label = null);

    public function getAsDto(): FieldDto;
}
