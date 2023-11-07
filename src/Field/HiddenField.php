<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class HiddenField extends AbstractField
{
    public static function new(string $propertyName, TranslatableInterface|string|null $label = null): FieldInterface
    {
        return parent::new($propertyName, $label)
            ->setTemplateName('crud/field/hidden')
            ->setFormType(HiddenType::class)
            ->addCssClass('field-hidden');
    }
}
