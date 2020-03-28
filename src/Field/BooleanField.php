<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/*
 * TODO: create a BooleanConfigurator to check if user can edit the entity.
 * If they can't, then disable the 'renderAsSwitch' option and make it false.
 */
final class BooleanField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_RENDER_AS_SWITCH = 'renderAsSwitch';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setFieldFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/boolean')
            ->setFormType(CheckboxType::class)
            ->addCssClass('field-boolean')
            ->setTextAlign('center')
            ->setCustomOption(self::OPTION_RENDER_AS_SWITCH, true);
    }

    public function renderAsSwitch(bool $isASwitch = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_AS_SWITCH, $isASwitch);

        return $this;
    }
}
