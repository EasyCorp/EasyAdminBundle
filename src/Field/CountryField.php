<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

class CountryField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_SHOW_FLAG = 'showFlag';
    public const OPTION_SHOW_NAME = 'showName';

    public function __construct()
    {
        $this
            ->setType('country')
            ->setFormType(CountryType::class)
            ->setTemplateName('crud/field/country')
            ->setCustomOption(self::OPTION_SHOW_FLAG, true)
            ->setCustomOption(self::OPTION_SHOW_NAME, true);
    }

    public function showFlag(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_FLAG, $isShown);

        return $this;
    }

    public function showName(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_NAME, $isShown);

        return $this;
    }
}
