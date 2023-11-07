<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

abstract class AbstractField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ICON = 'icon';

    public static function new(string $propertyName, TranslatableInterface|string|false|null $label = null): FieldInterface
    {
        if($label === false) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.8.4',
                'Passing `false` as the second argument of the "%s()" method is deprecated. Use `null` instead.',
                __METHOD__
            );
        }

        return (new static())
            ->setFieldFqcn(static::class)
            ->setProperty($propertyName)
            ->setLabel($label);
    }
}
