<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;

class SelectProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_CHOICES = 'choices';

    public function __construct()
    {
        $this
            ->setType('select')
            ->setFormType(ChoiceType::class)
            ->setTemplateName('property/select')
            ->setCustomOption(self::OPTION_CHOICES, null);
    }

    public function setChoices(array $keyValueChoices): self
    {
        $this->setCustomOption(self::OPTION_CHOICES, $keyValueChoices);

        return $this;
    }
}
