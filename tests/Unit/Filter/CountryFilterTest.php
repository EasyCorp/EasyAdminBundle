<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Filter\CountryFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use PHPUnit\Framework\TestCase;

class CountryFilterTest extends TestCase
{
    public function testNew(): void
    {
        $filter = CountryFilter::new('country');
        $dto = $filter->getAsDto();

        $this->assertSame('country', $dto->getProperty());
        $this->assertSame(CountryFilter::class, $dto->getFqcn());
        $this->assertSame(ChoiceFilterType::class, $dto->getFormType());
    }

    public function testNewWithLabel(): void
    {
        $filter = CountryFilter::new('country', 'Country Label');
        $dto = $filter->getAsDto();

        $this->assertSame('Country Label', $dto->getLabel());
    }

    public function testIncludeOnly(): void
    {
        $filter = CountryFilter::new('country')->includeOnly(['ES', 'FR', 'DE']);
        $dto = $filter->getAsDto();

        $this->assertSame(['ES', 'FR', 'DE'], $dto->getCustomOption(CountryFilter::OPTION_COUNTRY_CODES_TO_KEEP));
    }

    public function testRemove(): void
    {
        $filter = CountryFilter::new('country')->remove(['US', 'CA']);
        $dto = $filter->getAsDto();

        $this->assertSame(['US', 'CA'], $dto->getCustomOption(CountryFilter::OPTION_COUNTRY_CODES_TO_REMOVE));
    }

    public function testPreferredChoices(): void
    {
        $filter = CountryFilter::new('country')->preferredChoices(['ES', 'FR']);
        $dto = $filter->getAsDto();

        $this->assertSame(['ES', 'FR'], $dto->getCustomOption(CountryFilter::OPTION_PREFERRED_CHOICES));
    }

    public function testUseAlpha3Codes(): void
    {
        $filter = CountryFilter::new('country')->useAlpha3Codes();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getCustomOption(CountryFilter::OPTION_USE_ALPHA3_CODES));
    }

    public function testUseAlpha3CodesFalse(): void
    {
        $filter = CountryFilter::new('country')->useAlpha3Codes(false);
        $dto = $filter->getAsDto();

        $this->assertFalse($dto->getCustomOption(CountryFilter::OPTION_USE_ALPHA3_CODES));
    }

    public function testCanSelectMultiple(): void
    {
        $filter = CountryFilter::new('country')->canSelectMultiple();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
    }

    public function testRenderExpanded(): void
    {
        $filter = CountryFilter::new('country')->renderExpanded();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }

    public function testFluentInterface(): void
    {
        $filter = CountryFilter::new('country')
            ->includeOnly(['ES', 'FR', 'DE'])
            ->preferredChoices(['ES'])
            ->useAlpha3Codes()
            ->canSelectMultiple()
            ->renderExpanded();

        $dto = $filter->getAsDto();

        $this->assertSame(['ES', 'FR', 'DE'], $dto->getCustomOption(CountryFilter::OPTION_COUNTRY_CODES_TO_KEEP));
        $this->assertSame(['ES'], $dto->getCustomOption(CountryFilter::OPTION_PREFERRED_CHOICES));
        $this->assertTrue($dto->getCustomOption(CountryFilter::OPTION_USE_ALPHA3_CODES));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }
}
