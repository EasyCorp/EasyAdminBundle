<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class TimezoneField implements FieldInterface
{
    use FieldTrait;

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/timezone')
            ->setFormType(TimezoneType::class)
            ->addCssClass('field-timezone')
            ->setDefaultColumns('col-md-6 col-xxl-5');
    }
}
