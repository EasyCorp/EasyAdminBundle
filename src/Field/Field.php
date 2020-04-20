<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Field implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label);
    }
}
