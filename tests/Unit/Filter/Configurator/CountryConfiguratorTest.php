<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\CountryConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\CountryFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Countries;

class CountryConfiguratorTest extends TestCase
{
    private CountryConfigurator $configurator;
    private EntityDto $entityDto;
    private AdminContext $adminContext;

    protected function setUp(): void
    {
        $this->configurator = new CountryConfigurator();

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

    public function testSupportsCountryFilter(): void
    {
        $filter = CountryFilter::new('country');
        $filterDto = $filter->getAsDto();

        $this->assertTrue($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testDoesNotSupportOtherFilters(): void
    {
        $filter = TextFilter::new('name');
        $filterDto = $filter->getAsDto();

        $this->assertFalse($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testConfigureSetsAllCountries(): void
    {
        $filter = CountryFilter::new('country');
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');
        $expectedCountries = array_flip(Countries::getNames());

        $this->assertSame($expectedCountries, $choices);
        $this->assertFalse($filterDto->getFormTypeOption('value_type_options.choice_translation_domain'));
    }

    public function testConfigureWithIncludeOnly(): void
    {
        $filter = CountryFilter::new('country')->includeOnly(['ES', 'FR', 'DE']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertCount(3, $choices);
        $this->assertArrayHasKey(Countries::getName('ES'), $choices);
        $this->assertArrayHasKey(Countries::getName('FR'), $choices);
        $this->assertArrayHasKey(Countries::getName('DE'), $choices);
        $this->assertSame('ES', $choices[Countries::getName('ES')]);
    }

    public function testConfigureWithRemove(): void
    {
        $filter = CountryFilter::new('country')->remove(['US', 'CA']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertArrayNotHasKey(Countries::getName('US'), $choices);
        $this->assertArrayNotHasKey(Countries::getName('CA'), $choices);
        $this->assertArrayHasKey(Countries::getName('ES'), $choices);
    }

    public function testConfigureWithPreferredChoices(): void
    {
        $filter = CountryFilter::new('country')->preferredChoices(['ES', 'FR']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $preferredChoices = $filterDto->getFormTypeOption('value_type_options.preferred_choices');

        $this->assertSame(['ES', 'FR'], $preferredChoices);
    }

    public function testConfigureWithAlpha3Codes(): void
    {
        $filter = CountryFilter::new('country')->useAlpha3Codes()->includeOnly(['ESP', 'FRA']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertCount(2, $choices);
        $this->assertSame('ESP', $choices[Countries::getAlpha3Name('ESP')]);
        $this->assertSame('FRA', $choices[Countries::getAlpha3Name('FRA')]);
    }
}
