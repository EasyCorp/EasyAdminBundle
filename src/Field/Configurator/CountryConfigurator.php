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
    // This List reflects the src/Resources/public/images/flags png collection
    private const SUPPORTED_FLAGS = "AD AE AF AG AL AM AR AT AU AZ BA BB BD BE BF BG BH BI BJ BN BO BR BS BT BW BY BZ CA CD CF CG CH CI CL CM CN CO CR CU CV CY CZ DE DJ DK DM DO DZ EC EE EG ER ES ET FI FJ FM FR GA GB GD GE GH GM GN GQ GR GT GW GY HK HN HR HT HU ID IE IL IN IQ IR IS IT JM JO JP KE KG KH KI KM KN KP KR KW KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MG MH MK ML MM MN MR MT MU MV MW MX MY MZ NA NE NG NI NL NO NP NR NZ OM PA PE PG PH PK PL PR PS PT PW PY QA RO RS RU RW SA SB SC SD SE SG SI SK SL SM SN SO SR SS ST SV SY SZ TD TG TH TJ TL TM TN TO TR TT TV TW TZ UA UG US UY UZ VA VC VE VN VU WS XK YE ZA ZM ZW";

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
            $flagCode = $countryCode;

            if (CountryField::FORMAT_ISO_3166_ALPHA3 === $countryCodeFormat) {
                $flagCode = Countries::getAlpha2Code($flagCode);
            }

            if (empty($flagCode) || false === strpos(self::SUPPORTED_FLAGS, $flagCode)) {
                $flagCode = 'GENERIC';
            }

            return $flagCode;
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
