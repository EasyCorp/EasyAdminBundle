<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\TimezoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TimezoneFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Timezones;

class TimezoneConfiguratorTest extends TestCase
{
    private TimezoneConfigurator $configurator;
    private EntityDto $entityDto;
    private AdminContext $adminContext;

    protected function setUp(): void
    {
        $this->configurator = new TimezoneConfigurator();

        $metadata = new ClassMetadata('App\Entity\Test');
        $metadata->setIdentifier(['id']);
        $this->entityDto = new EntityDto('App\Entity\Test', $metadata);

        $this->adminContext = AdminContext::forTesting(
            RequestContext::forTesting(new Request()),
            null,
            null,
            I18nContext::forTesting('en', 'ltr')
        );
    }

    public function testSupportsTimezoneFilter(): void
    {
        $filter = TimezoneFilter::new('timezone');
        $filterDto = $filter->getAsDto();

        $this->assertTrue($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testDoesNotSupportOtherFilters(): void
    {
        $filter = TextFilter::new('name');
        $filterDto = $filter->getAsDto();

        $this->assertFalse($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testConfigureSetsAllTimezones(): void
    {
        $filter = TimezoneFilter::new('timezone');
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');
        $expectedTimezones = array_flip(Timezones::getNames());

        $this->assertSame($expectedTimezones, $choices);
        $this->assertFalse($filterDto->getFormTypeOption('value_type_options.choice_translation_domain'));
    }

    public function testConfigureWithForCountryCode(): void
    {
        $filter = TimezoneFilter::new('timezone')->forCountryCode('ES');
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');
        $spanishTimezones = Timezones::forCountryCode('ES');

        $this->assertCount(\count($spanishTimezones), $choices);
        foreach ($spanishTimezones as $timezone) {
            $this->assertContains($timezone, $choices);
        }
    }

    public function testConfigureWithIncludeOnly(): void
    {
        $filter = TimezoneFilter::new('timezone')->includeOnly(['Europe/Madrid', 'Europe/Paris', 'America/New_York']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertCount(3, $choices);
        $this->assertArrayHasKey(Timezones::getName('Europe/Madrid'), $choices);
        $this->assertArrayHasKey(Timezones::getName('Europe/Paris'), $choices);
        $this->assertArrayHasKey(Timezones::getName('America/New_York'), $choices);
        $this->assertSame('Europe/Madrid', $choices[Timezones::getName('Europe/Madrid')]);
    }

    public function testConfigureWithRemove(): void
    {
        $filter = TimezoneFilter::new('timezone')->remove(['Europe/London', 'Europe/Paris']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertArrayNotHasKey(Timezones::getName('Europe/London'), $choices);
        $this->assertArrayNotHasKey(Timezones::getName('Europe/Paris'), $choices);
        $this->assertArrayHasKey(Timezones::getName('Europe/Madrid'), $choices);
    }

    public function testConfigureWithPreferredChoices(): void
    {
        $filter = TimezoneFilter::new('timezone')->preferredChoices(['Europe/Madrid', 'Europe/Paris']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $preferredChoices = $filterDto->getFormTypeOption('value_type_options.preferred_choices');

        $this->assertSame(['Europe/Madrid', 'Europe/Paris'], $preferredChoices);
    }

    public function testConfigureForCountryCodeWithIncludeOnly(): void
    {
        $filter = TimezoneFilter::new('timezone')
            ->forCountryCode('US')
            ->includeOnly(['America/New_York', 'America/Los_Angeles']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertCount(2, $choices);
        $this->assertContains('America/New_York', $choices);
        $this->assertContains('America/Los_Angeles', $choices);
    }
}
