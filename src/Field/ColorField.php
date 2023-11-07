<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ColorField extends AbstractField
{
    public const OPTION_SHOW_SAMPLE = 'showSample';
    public const OPTION_SHOW_VALUE = 'showValue';

    public static function new(string $propertyName, TranslatableInterface|string|false|null $label = null): FieldInterface
    {
        return parent::new($propertyName, $label)
            ->setTemplateName('crud/field/color')
            ->setFormType(ColorType::class)
            ->addCssClass('field-color')
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
