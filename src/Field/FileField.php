<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FileField extends AbstractFileField
{
    public static function new(string $propertyName, ?string $label = null): AbstractFileField
    {
        return (parent::new($propertyName, $label))
            ->setTemplateName('crud/field/file')
            ->addCssClass('field-file');
    }
}
