<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class SelectField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_AUTOCOMPLETE = 'autocomplete';
    public const OPTION_CHOICES = 'choices';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setFieldFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/select')
            ->setFormType(ChoiceType::class)
            ->addCssClass('field-select')
            ->setCustomOption(self::OPTION_CHOICES, null);
    }

    public function autocomplete(): self
    {
        $this->setCustomOption(self::OPTION_AUTOCOMPLETE, true);

        return $this;
    }

    public function setChoices(array $keyValueChoices): self
    {
        $this->setCustomOption(self::OPTION_CHOICES, $keyValueChoices);

        return $this;
    }
}
