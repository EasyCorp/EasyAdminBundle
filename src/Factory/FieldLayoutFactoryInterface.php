<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldLayoutDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal and @experimental don't use this in your own apps
 */
interface FieldLayoutFactoryInterface
{
    public static function createFromFieldDtos(FieldCollection|null $fieldDtos): FieldLayoutDtoInterface;
}
