<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Field extends AbstractField
{
    public static function new(string $propertyName, TranslatableInterface|string|null $label = null): FieldInterface
    {
        return parent::new($propertyName, $label);
    }
}
