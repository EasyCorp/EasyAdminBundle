<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Field implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null, ?string $templateName = null): self
    {
        if (null === $templateName) {
            $templateName = 'crud/field/generic';
        }
        
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName($templateName);
    }
}
