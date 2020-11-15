<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CountryField implements FieldInterface
{
    use FieldTrait;

    public const FORMAT_ISO_3166_ALPHA2 = 'iso3166Alpha2';
    public const FORMAT_ISO_3166_ALPHA3 = 'iso3166Alpha3';

    public const OPTION_SHOW_FLAG = 'showFlag';
    public const OPTION_SHOW_NAME = 'showName';
    public const OPTION_COUNTRY_CODE_FORMAT = 'countryCodeFormat';
    /** @internal used to store the code of the flag to use independently from the country code format used */
    public const OPTION_FLAG_CODE = 'flagCode';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/country')
            ->setFormType(ChoiceType::class)
            ->addCssClass('field-country')
            ->setCustomOption(self::OPTION_SHOW_FLAG, true)
            ->setCustomOption(self::OPTION_SHOW_NAME, true)
            ->setCustomOption(self::OPTION_COUNTRY_CODE_FORMAT, self::FORMAT_ISO_3166_ALPHA2);
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

    public function useAlpha3Codes(bool $useAlpha3 = true): self
    {
        $this->setCustomOption(self::OPTION_COUNTRY_CODE_FORMAT, $useAlpha3 ? self::FORMAT_ISO_3166_ALPHA3 : self::FORMAT_ISO_3166_ALPHA2);

        return $this;
    }
}
