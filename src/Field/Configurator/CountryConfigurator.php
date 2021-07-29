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
    // This list reflects the PNG files stored in src/Resources/public/images/flags/
    private const FLAGS_WITH_IMAGE_FILE = [
        'AD', 'AE', 'AF', 'AG', 'AL', 'AM', 'AR', 'AT', 'AU', 'AZ', 'BA', 'BB',
        'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BN', 'BO', 'BR', 'BS', 'BT',
        'BW', 'BY', 'BZ', 'CA', 'CD', 'CF', 'CG', 'CH', 'CI', 'CL', 'CM', 'CN',
        'CO', 'CR', 'CU', 'CV', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ',
        'EC', 'EE', 'EG', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FM', 'FR', 'GA', 'GB',
        'GD', 'GE', 'GF', 'GH', 'GM', 'GN', 'GQ', 'GR', 'GT', 'GW', 'GY', 'HK',
        'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IN', 'IQ', 'IR', 'IS', 'IT',
        'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW',
        'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
        'MA', 'MC', 'MD', 'ME', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MR', 'MT',
        'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NG', 'NI', 'NL',
        'NO', 'NP', 'NR', 'NZ', 'OM', 'PA', 'PE', 'PG', 'PH', 'PK', 'PL', 'PR',
        'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB',
        'SD', 'SE', 'SG', 'SI', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'SC',
        'SV', 'SY', 'SZ', 'TD', 'TG', 'TH', 'TJ', 'TL', 'TM', 'TN', 'TO', 'ST',
        'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'US', 'UY', 'UZ', 'VA', 'VC', 'TR',
        'VN', 'VU', 'WS', 'XK', 'YE', 'ZA', 'ZM', 'ZW', 'VE',
    ];

    private $assetPackages;

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
            $field->setFormTypeOption('choices', $this->generateFormTypeChoices($countryCodeFormat));

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

    private function generateFormTypeChoices(string $countryCodeFormat): array
    {
        $usesAlpha3Codes = CountryField::FORMAT_ISO_3166_ALPHA3 === $countryCodeFormat;
        $choices = [];

        $countries = $usesAlpha3Codes ? Countries::getAlpha3Names() : Countries::getNames();
        foreach ($countries as $countryCode => $countryName) {
            $countryCodeAlpha2 = $usesAlpha3Codes ? Countries::getAlpha2Code($countryCode) : $countryCode;
            $flagImageName = \in_array($countryCodeAlpha2, self::FLAGS_WITH_IMAGE_FILE, true) ? $countryCodeAlpha2 : 'UNKNOWN';
            $flagImagePath = $this->assetPackages->getUrl(sprintf('bundles/easyadmin/images/flags/%s.png', $flagImageName));
            $choiceKey = sprintf('<img src="%s" class="country-flag" loading="lazy" alt="%s"> %s', $flagImagePath, $countryName, $countryName);

            $choices[$choiceKey] = $countryCode;
        }

        return $choices;
    }
}
