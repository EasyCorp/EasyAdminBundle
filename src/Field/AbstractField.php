<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

abstract class AbstractField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ICON = 'icon';

    public static function new(string $propertyName, TranslatableInterface|string|null $label = null): FieldInterface
    {
        return (new static())
            ->setFieldFqcn(static::class)
            ->setProperty($propertyName)
            ->setLabel($label);
    }
}
