<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TelephoneField extends AbstractField
{
    public static function new(string $propertyName, TranslatableInterface|string|null $label = null): FieldInterface
    {
        return parent::new($propertyName, $label)
            ->setTemplateName('crud/field/telephone')
            ->setFormType(TelType::class)
            ->addCssClass('field-telephone')
            ->setDefaultColumns('col-md-4 col-xxl-3');
    }
}
