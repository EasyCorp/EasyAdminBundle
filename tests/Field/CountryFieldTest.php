<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CountryConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class CountryFieldTest extends AbstractFieldTest
{
    private const NUM_COUNTRIES_AND_REGIONS = 249;

    protected function setUp(): void
    {
        parent::setUp();

        $assetPackage = new Package(new EmptyVersionStrategy());
        $this->configurator = new CountryConfigurator($assetPackage);
    }

    public function testDefaultFieldOptions()
    {
        $field = CountryField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame('ea-autocomplete', $fieldDto->getFormTypeOption('attr.data-ea-widget'));

        self::assertTrue($fieldDto->getCustomOption(CountryField::OPTION_SHOW_NAME));
        self::assertTrue($fieldDto->getCustomOption(CountryField::OPTION_SHOW_FLAG));
        self::assertSame(CountryField::FORMAT_ISO_3166_ALPHA2, $fieldDto->getCustomOption(CountryField::OPTION_COUNTRY_CODE_FORMAT));
        self::assertNull($fieldDto->getCustomOption(CountryField::OPTION_COUNTRY_CODES_TO_KEEP));
        self::assertNull($fieldDto->getCustomOption(CountryField::OPTION_COUNTRY_CODES_TO_REMOVE));
        self::assertFalse($fieldDto->getCustomOption(CountryField::OPTION_ALLOW_MULTIPLE_CHOICES));
    }

    public function testDefaultOptionsForFormPages()
    {
        $field = CountryField::new('foo');
        $fieldDto = $this->configure($field, pageName: Crud::PAGE_NEW);
        $formSelectChoices = $fieldDto->getFormTypeOption(ChoiceField::OPTION_CHOICES);

        self::assertCount(self::NUM_COUNTRIES_AND_REGIONS, $formSelectChoices);
        self::assertSame('GQ', $formSelectChoices['<div class="country-name-flag"><img src="images/flags/GQ.svg" height="17" class="country-flag" loading="lazy" alt="Equatorial Guinea"> <span>Equatorial Guinea</span></div>']);
        self::assertSame('true', $fieldDto->getFormTypeOption('attr.data-ea-autocomplete-render-items-as-html'));
    }

    public function testUnknownCountryCode()
    {
        $field = CountryField::new('foo');
        // the 'es' value is wrong on purpose: country codes must be uppercase
        $field->setValue('es');
        $fieldDto = $this->configure($field);

        self::assertSame('es', $fieldDto->getValue());
        self::assertSame(['UNKNOWN' => 'Unknown "es" country code'], $fieldDto->getFormattedValue());

        // the 'es' value is wrong on purpose: country codes must be uppercase
        $field->setValue(['es', 'KR']);
        $fieldDto = $this->configure($field);

        self::assertSame(['es', 'KR'], $fieldDto->getValue());
        self::assertSame(['UNKNOWN' => 'Unknown "es" country code', 'KR' => 'South Korea'], $fieldDto->getFormattedValue());
    }

    public function testSingleCountryCode()
    {
        $field = CountryField::new('foo');
        $field->setValue('ES');
        $fieldDto = $this->configure($field);

        self::assertSame('ES', $fieldDto->getValue());
        self::assertSame(['ES' => 'Spain'], $fieldDto->getFormattedValue());

        $fieldDto = $this->configure($field, requestLocale: 'es');
        self::assertSame('ES', $fieldDto->getValue());
        self::assertSame(['ES' => 'España'], $fieldDto->getFormattedValue());
    }

    public function testMultipleCountryCodes()
    {
        $field = CountryField::new('foo');
        $field->setValue(['BD', 'PG', 'SV']);
        $fieldDto = $this->configure($field);

        self::assertSame(['BD', 'PG', 'SV'], $fieldDto->getValue());
        self::assertSame(['BD' => 'Bangladesh', 'PG' => 'Papua New Guinea', 'SV' => 'El Salvador'], $fieldDto->getFormattedValue());

        $fieldDto = $this->configure($field, requestLocale: 'uk');
        self::assertSame(['BD', 'PG', 'SV'], $fieldDto->getValue());
        self::assertSame(['BD' => 'Бангладеш', 'PG' => 'Папуа-Нова Гвінея', 'SV' => 'Сальвадор'], $fieldDto->getFormattedValue());
    }

    public function testRemovingSomeCountries()
    {
        $field = CountryField::new('foo');
        $field->remove(['AF', 'KP']);
        $fieldDto = $this->configure($field, pageName: Crud::PAGE_EDIT);
        $formSelectChoices = $fieldDto->getFormTypeOption(ChoiceField::OPTION_CHOICES);
        $formSelectChoicesWithCountryCodesAsKeys = array_flip($formSelectChoices);

        self::assertCount(self::NUM_COUNTRIES_AND_REGIONS - 2, $formSelectChoices);
        self::assertArrayNotHasKey('AF', $formSelectChoicesWithCountryCodesAsKeys);
        self::assertArrayNotHasKey('KP', $formSelectChoicesWithCountryCodesAsKeys);
    }

    public function testShowingOnlySomeCountries()
    {
        $menFootballWorldCupWinnerCountries = ['BR', 'DE', 'IT', 'AR', 'FR', 'UY', 'GB', 'ES'];
        $countryCodesSortedAlphabeticallyByCounryEnglishName = ['AR', 'BR', 'FR', 'DE', 'IT', 'ES', 'GB', 'UY'];
        $field = CountryField::new('foo');
        $field->includeOnly($menFootballWorldCupWinnerCountries);
        $fieldDto = $this->configure($field, pageName: Crud::PAGE_EDIT);
        $formSelectChoices = $fieldDto->getFormTypeOption(ChoiceField::OPTION_CHOICES);

        self::assertSame($countryCodesSortedAlphabeticallyByCounryEnglishName, array_values($formSelectChoices));
    }

    public function testShowingWrongCountryCodeInForms()
    {
        $field = CountryField::new('foo');
        // the 'RR' country code does not exist
        $field->includeOnly(['CL', 'RR', 'EG']);
        $fieldDto = $this->configure($field, pageName: Crud::PAGE_EDIT);
        $formSelectChoices = $fieldDto->getFormTypeOption(ChoiceField::OPTION_CHOICES);

        self::assertCount(2, $formSelectChoices);
        self::assertSame(['CL', 'EG'], array_values($formSelectChoices));
    }

    public function testSelectingMultipleChoices()
    {
        $field = CountryField::new('foo');
        $field->allowMultipleChoices();
        $fieldDto = $this->configure($field, pageName: Crud::PAGE_EDIT);

        self::assertTrue($fieldDto->getCustomOption(CountryField::OPTION_ALLOW_MULTIPLE_CHOICES));
        self::assertTrue($fieldDto->getFormTypeOption('multiple'));
    }

    public function testUsingAlpha3Format()
    {
        $field = CountryField::new('foo');
        $field->useAlpha3Codes();

        // alpha3 shows the right default choices in forms
        $fieldDto = $this->configure($field, pageName: Crud::PAGE_EDIT);
        $formSelectChoices = $fieldDto->getFormTypeOption(ChoiceField::OPTION_CHOICES);
        $formSelectChoicesWithCountryCodesAsKeys = array_flip($formSelectChoices);
        self::assertCount(self::NUM_COUNTRIES_AND_REGIONS, $formSelectChoices);
        self::assertArrayHasKey('MEX', $formSelectChoicesWithCountryCodesAsKeys);
        self::assertArrayNotHasKey('MX', $formSelectChoicesWithCountryCodesAsKeys);

        // valid alpha3 country code in index/detail pages
        $field->setValue('MEX');
        $fieldDto = $this->configure($field);
        self::assertSame('MEX', $fieldDto->getValue());
        self::assertSame(['MX' => 'Mexico'], $fieldDto->getFormattedValue());

        // valid alpha3 country code and localized
        $field->setValue('MEX');
        $fieldDto = $this->configure($field, requestLocale: 'bg');
        self::assertSame('MEX', $fieldDto->getValue());
        self::assertSame(['MX' => 'Мексико'], $fieldDto->getFormattedValue());

        // invalid alpha3 country code
        $field->setValue('MX');
        $fieldDto = $this->configure($field);
        self::assertSame('MX', $fieldDto->getValue());
        self::assertSame(['UNKNOWN' => 'Unknown "MX" country code'], $fieldDto->getFormattedValue());

        // multiple valid alpha3 country codes
        $field->setValue(['MEX', 'VNM']);
        $fieldDto = $this->configure($field);
        self::assertSame(['MEX', 'VNM'], $fieldDto->getValue());
        self::assertSame(['MX' => 'Mexico', 'VN' => 'Vietnam'], $fieldDto->getFormattedValue());
    }
}
