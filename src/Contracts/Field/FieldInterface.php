<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Field;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FieldInterface
{
    public static function new(string $propertyName, ?string /* TranslatableInterface|string|false|null */ $label = null);

    public function getAsDto(): FieldDtoInterface;
}
