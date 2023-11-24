<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TimezoneField extends AbstractField
{
    public static function new(string $propertyName, TranslatableInterface|string|false|null $label = null): FieldInterface
    {
        return parent::new($propertyName, $label)
            ->setTemplateName('crud/field/timezone')
            ->setFormType(TimezoneType::class)
            ->addCssClass('field-timezone')
            ->setDefaultColumns('col-md-6 col-xxl-5');
    }
}
