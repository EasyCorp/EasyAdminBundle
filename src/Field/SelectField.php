<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SelectField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_AUTOCOMPLETE = 'autocomplete';
    public const OPTION_CHOICES = 'choices';

    public function __construct()
    {
        $this
            ->setType('select')
            ->setFormType(ChoiceType::class)
            ->setTemplateName('crud/field/select')
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
