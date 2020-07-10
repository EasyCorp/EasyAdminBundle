<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CountryConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return CountryField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');
        $countryCodeFormat = $field->getCustomOption(CountryField::OPTION_COUNTRY_CODE_FORMAT);

        if (CountryField::FORMAT_ISO_3166_ALPHA3 === $countryCodeFormat) {
            $field->setFormTypeOption('alpha3', true);
        }

        $field->setCustomOption(CountryField::OPTION_FLAG_CODE, $this->getFlagCode($field->getValue(), $countryCodeFormat));
        $field->setFormattedValue($this->getCountryName($field->getValue(), $countryCodeFormat));

        if (null === $field->getTextAlign() && false === $field->getCustomOption(CountryField::OPTION_SHOW_NAME)) {
            $field->setTextAlign('center');
        }
    }

    private function getCountryName(?string $countryCode, string $countryCodeFormat): ?string
    {
        if (null === $countryCode) {
            return null;
        }

        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Countries::class)) {
            return Intl::getRegionBundle()->getCountryName($countryCode);
        }

        try {
            if (CountryField::FORMAT_ISO_3166_ALPHA3 === $countryCodeFormat) {
                return Countries::getAlpha3Name($countryCode);
            }

            return Countries::getName($countryCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }

    private function getFlagCode(?string $countryCode, string $countryCodeFormat): ?string
    {
        if (null === $countryCode) {
            return null;
        }

        try {
            if (CountryField::FORMAT_ISO_3166_ALPHA3 === $countryCodeFormat) {
                return Countries::getAlpha2Code($countryCode);
            }

            return $countryCode;
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
