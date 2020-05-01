<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\SlugType;

/**
 * @author Jonathan Scheiber <contact@jmsche.fr>
 */
final class SlugField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text')
            ->setFormType(SlugType::class)
            ->addCssClass('field-text')
            ->addJsFiles('bundles/easyadmin/form-type-slug.js')
        ;
    }
}
