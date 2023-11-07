<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ArrayField extends AbstractField
{
    public static function new(string $propertyName, TranslatableInterface|string|null $label = null): FieldInterface
    {
        return parent::new($propertyName, $label)
            ->setTemplateName('crud/field/array')
            ->setFormType(CollectionType::class)
            ->addCssClass('field-array')
            ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-collection.js')->onlyOnForms())
            ->setDefaultColumns('col-md-7 col-xxl-6');
    }
}
