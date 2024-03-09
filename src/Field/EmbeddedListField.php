<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EmbeddedListType;

class EmbeddedListField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ENTRY_IS_COMPLEX = 'entryIsComplex';

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = false): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('@EasyAdmin/crud/field/association.html.twig')
            ->setFormType(EmbeddedListType::class)
            ->setCustomOption(self::OPTION_ENTRY_IS_COMPLEX, null)
            ->setVirtual(true)
        ;
    }
}
