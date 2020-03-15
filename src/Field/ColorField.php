<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\ColorType;

class ColorField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_SHOW_SAMPLE = 'showSample';
    public const OPTION_SHOW_VALUE = 'showValue';

    public function __construct()
    {
        $this
            ->setType('color')
            ->setFormType(ColorType::class)
            ->setTemplateName('crud/field/color')
            ->setCustomOption(self::OPTION_SHOW_SAMPLE, true)
            ->setCustomOption(self::OPTION_SHOW_VALUE, false);
    }

    public function showSample(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_SAMPLE, $isShown);

        return $this;
    }

    public function showValue(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_VALUE, $isShown);

        return $this;
    }
}
