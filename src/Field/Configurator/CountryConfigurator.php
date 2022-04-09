<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CountryConfigurator implements FieldConfiguratorInterface
{
    // This list reflects the country codes returned by Symfony Intl component, which in
    // turn uses the ICU Project list of countries and territories. Don't add or remove
    // any element in this list; changes must be made in ICU and updated via Symfony Intl.
    private const FLAGS_WITH_IMAGE_FILE = [
        'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT',
        'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI',
        'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY',
        'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
        'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM',
        'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK',
        'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL',
        'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
        'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR',
        'IS', 'IT', 'JE', 'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN',
        'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS',
        'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
        'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW',
        'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP',
        'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM',
        'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
        'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM',
        'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF',
        'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW',
        'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
        'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
    ];

    private Packages $assetPackages;

    public function __construct(Packages $assetPackages)
    {
        $this->assetPackages = $assetPackages;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return CountryField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOption('attr.data-ea-widget', 'ea-autocomplete');
        $countryCodeFormat = $field->getCustomOption(CountryField::OPTION_COUNTRY_CODE_FORMAT);

        $field->setCustomOption(CountryField::OPTION_FLAG_CODE, $this->getFlagCode($field->getValue(), $countryCodeFormat));
        $field->setFormattedValue($this->getCountryName($field->getValue(), $countryCodeFormat));

        if (null === $field->getTextAlign() && false === $field->getCustomOption(CountryField::OPTION_SHOW_NAME)) {
            $field->setTextAlign(TextAlign::CENTER);
        }

        if (\in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            $field->setFormTypeOption('choices', $this->generateFormTypeChoices($countryCodeFormat, $field->getCustomOption(CountryField::OPTION_COUNTRY_CODES_TO_KEEP), $field->getCustomOption(CountryField::OPTION_COUNTRY_CODES_TO_REMOVE)));

            // the value of this form option must be a string to properly propagate it as an HTML attribute value
            $field->setFormTypeOption('attr.data-ea-autocomplete-render-items-as-html', 'true');
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

            if (empty($flagCode) || !\in_array($flagCode, self::FLAGS_WITH_IMAGE_FILE)) {
                $flagCode = 'UNKNOWN';
            }

            return $flagCode;
        } catch (MissingResourceException $e) {
            return null;
        }
    }

    private function generateFormTypeChoices(string $countryCodeFormat, ?array $countryCodesToKeep, ?array $countryCodesToRemove): array
    {
        $usesAlpha3Codes = CountryField::FORMAT_ISO_3166_ALPHA3 === $countryCodeFormat;
        $choices = [];

        $countries = $usesAlpha3Codes ? Countries::getAlpha3Names() : Countries::getNames();
        foreach ($countries as $countryCode => $countryName) {
            if (null !== $countryCodesToKeep && !\in_array($countryCode, $countryCodesToKeep, true)) {
                continue;
            }

            if (null !== $countryCodesToRemove && \in_array($countryCode, $countryCodesToRemove, true)) {
                continue;
            }

            $countryCodeAlpha2 = $usesAlpha3Codes ? Countries::getAlpha2Code($countryCode) : $countryCode;
            $flagImageName = \in_array($countryCodeAlpha2, self::FLAGS_WITH_IMAGE_FILE, true) ? $countryCodeAlpha2 : 'UNKNOWN';
            $flagImagePath = $this->assetPackages->getUrl(sprintf('bundles/easyadmin/images/flags/%s.svg', $flagImageName));
            $choiceKey = sprintf('<img src="%s" class="country-flag" loading="lazy" alt="%s"> %s', $flagImagePath, $countryName, $countryName);

            $choices[$choiceKey] = $countryCode;
        }

        return $choices;
    }
}
