<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TelType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TelephoneField implements FieldInterface
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
            ->setTemplateName('crud/field/telephone')
            ->setFormType(TelType::class)
            ->addCssClass('field-telephone')
            ->setDefaultColumns('col-md-4 col-xxl-3');
    }
}
