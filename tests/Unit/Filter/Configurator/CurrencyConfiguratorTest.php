<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\CurrencyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\CurrencyFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Currencies;

class CurrencyConfiguratorTest extends TestCase
{
    private CurrencyConfigurator $configurator;
    private EntityDto $entityDto;
    private AdminContext $adminContext;

    protected function setUp(): void
    {
        $this->configurator = new CurrencyConfigurator();

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

    public function testSupportsCurrencyFilter(): void
    {
        $filter = CurrencyFilter::new('currency');
        $filterDto = $filter->getAsDto();

        $this->assertTrue($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testDoesNotSupportOtherFilters(): void
    {
        $filter = TextFilter::new('name');
        $filterDto = $filter->getAsDto();

        $this->assertFalse($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testConfigureSetsAllCurrencies(): void
    {
        $filter = CurrencyFilter::new('currency');
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');
        $expectedCurrencies = array_flip(Currencies::getNames());

        $this->assertSame($expectedCurrencies, $choices);
        $this->assertFalse($filterDto->getFormTypeOption('value_type_options.choice_translation_domain'));
    }

    public function testConfigureWithIncludeOnly(): void
    {
        $filter = CurrencyFilter::new('currency')->includeOnly(['EUR', 'USD', 'GBP']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertCount(3, $choices);
        $this->assertArrayHasKey(Currencies::getName('EUR'), $choices);
        $this->assertArrayHasKey(Currencies::getName('USD'), $choices);
        $this->assertArrayHasKey(Currencies::getName('GBP'), $choices);
        $this->assertSame('EUR', $choices[Currencies::getName('EUR')]);
    }

    public function testConfigureWithRemove(): void
    {
        $filter = CurrencyFilter::new('currency')->remove(['EUR', 'USD']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertArrayNotHasKey(Currencies::getName('EUR'), $choices);
        $this->assertArrayNotHasKey(Currencies::getName('USD'), $choices);
        $this->assertArrayHasKey(Currencies::getName('GBP'), $choices);
    }

    public function testConfigureWithPreferredChoices(): void
    {
        $filter = CurrencyFilter::new('currency')->preferredChoices(['EUR', 'USD']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $preferredChoices = $filterDto->getFormTypeOption('value_type_options.preferred_choices');

        $this->assertSame(['EUR', 'USD'], $preferredChoices);
    }
}
