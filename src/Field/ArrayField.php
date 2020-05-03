<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ArrayField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/array')
            ->setFormType(CollectionType::class)
            ->addCssClass('field-array')
            ->addJsFiles('bundles/easyadmin/form-type-collection.js');
    }
}
