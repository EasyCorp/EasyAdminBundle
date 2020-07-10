<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CountryField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_SHOW_FLAG = 'showFlag';
    public const OPTION_SHOW_NAME = 'showName';
    public const OPTION_ALPHA3 = 'alpha3';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/country')
            ->setFormType(CountryType::class)
            ->addCssClass('field-country')
            ->setCustomOption(self::OPTION_SHOW_FLAG, true)
            ->setCustomOption(self::OPTION_SHOW_NAME, true)
            ->setCustomOption(self::OPTION_ALPHA3, false);
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

    public function alpha3(bool $enable = true): self
    {
        $this->setCustomOption(self::OPTION_ALPHA3, $enable);

        return $this;
    }
}
