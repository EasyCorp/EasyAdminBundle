<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class HiddenField implements FieldInterface
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
            ->setTemplateName('crud/field/hidden')
            ->setFormType(HiddenType::class)
            ->addCssClass('field-hidden');
    }
}
