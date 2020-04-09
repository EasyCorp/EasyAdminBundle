<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EmailField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/email')
            ->setFormType(EmailType::class)
            ->addCssClass('field-email');
    }
}
