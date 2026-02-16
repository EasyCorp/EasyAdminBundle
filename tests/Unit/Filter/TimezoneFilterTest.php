<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Filter\TimezoneFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use PHPUnit\Framework\TestCase;

class TimezoneFilterTest extends TestCase
{
    public function testNew(): void
    {
        $filter = TimezoneFilter::new('timezone');
        $dto = $filter->getAsDto();

        $this->assertSame('timezone', $dto->getProperty());
        $this->assertSame(TimezoneFilter::class, $dto->getFqcn());
        $this->assertSame(ChoiceFilterType::class, $dto->getFormType());
    }

    public function testNewWithLabel(): void
    {
        $filter = TimezoneFilter::new('timezone', 'Timezone Label');
        $dto = $filter->getAsDto();

        $this->assertSame('Timezone Label', $dto->getLabel());
    }

    public function testIncludeOnly(): void
    {
        $filter = TimezoneFilter::new('timezone')->includeOnly(['Europe/Madrid', 'Europe/Paris', 'America/New_York']);
        $dto = $filter->getAsDto();

        $this->assertSame(['Europe/Madrid', 'Europe/Paris', 'America/New_York'], $dto->getCustomOption(TimezoneFilter::OPTION_TIMEZONE_IDENTIFIERS_TO_KEEP));
    }

    public function testRemove(): void
    {
        $filter = TimezoneFilter::new('timezone')->remove(['UTC', 'GMT']);
        $dto = $filter->getAsDto();

        $this->assertSame(['UTC', 'GMT'], $dto->getCustomOption(TimezoneFilter::OPTION_TIMEZONE_IDENTIFIERS_TO_REMOVE));
    }

    public function testPreferredChoices(): void
    {
        $filter = TimezoneFilter::new('timezone')->preferredChoices(['Europe/Madrid', 'Europe/Paris']);
        $dto = $filter->getAsDto();

        $this->assertSame(['Europe/Madrid', 'Europe/Paris'], $dto->getCustomOption(TimezoneFilter::OPTION_PREFERRED_CHOICES));
    }

    public function testForCountryCode(): void
    {
        $filter = TimezoneFilter::new('timezone')->forCountryCode('ES');
        $dto = $filter->getAsDto();

        $this->assertSame('ES', $dto->getCustomOption(TimezoneFilter::OPTION_FOR_COUNTRY_CODE));
    }

    public function testCanSelectMultiple(): void
    {
        $filter = TimezoneFilter::new('timezone')->canSelectMultiple();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
    }

    public function testRenderExpanded(): void
    {
        $filter = TimezoneFilter::new('timezone')->renderExpanded();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }

    public function testFluentInterface(): void
    {
        $filter = TimezoneFilter::new('timezone')
            ->forCountryCode('ES')
            ->preferredChoices(['Europe/Madrid'])
            ->canSelectMultiple()
            ->renderExpanded();

        $dto = $filter->getAsDto();

        $this->assertSame('ES', $dto->getCustomOption(TimezoneFilter::OPTION_FOR_COUNTRY_CODE));
        $this->assertSame(['Europe/Madrid'], $dto->getCustomOption(TimezoneFilter::OPTION_PREFERRED_CHOICES));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }
}
