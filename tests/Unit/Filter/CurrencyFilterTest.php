<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Filter\CurrencyFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use PHPUnit\Framework\TestCase;

class CurrencyFilterTest extends TestCase
{
    public function testNew(): void
    {
        $filter = CurrencyFilter::new('currency');
        $dto = $filter->getAsDto();

        $this->assertSame('currency', $dto->getProperty());
        $this->assertSame(CurrencyFilter::class, $dto->getFqcn());
        $this->assertSame(ChoiceFilterType::class, $dto->getFormType());
    }

    public function testNewWithLabel(): void
    {
        $filter = CurrencyFilter::new('currency', 'Currency Label');
        $dto = $filter->getAsDto();

        $this->assertSame('Currency Label', $dto->getLabel());
    }

    public function testIncludeOnly(): void
    {
        $filter = CurrencyFilter::new('currency')->includeOnly(['EUR', 'USD', 'GBP']);
        $dto = $filter->getAsDto();

        $this->assertSame(['EUR', 'USD', 'GBP'], $dto->getCustomOption(CurrencyFilter::OPTION_CURRENCY_CODES_TO_KEEP));
    }

    public function testRemove(): void
    {
        $filter = CurrencyFilter::new('currency')->remove(['XBT', 'XXX']);
        $dto = $filter->getAsDto();

        $this->assertSame(['XBT', 'XXX'], $dto->getCustomOption(CurrencyFilter::OPTION_CURRENCY_CODES_TO_REMOVE));
    }

    public function testPreferredChoices(): void
    {
        $filter = CurrencyFilter::new('currency')->preferredChoices(['EUR', 'USD']);
        $dto = $filter->getAsDto();

        $this->assertSame(['EUR', 'USD'], $dto->getCustomOption(CurrencyFilter::OPTION_PREFERRED_CHOICES));
    }

    public function testCanSelectMultiple(): void
    {
        $filter = CurrencyFilter::new('currency')->canSelectMultiple();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
    }

    public function testRenderExpanded(): void
    {
        $filter = CurrencyFilter::new('currency')->renderExpanded();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }

    public function testFluentInterface(): void
    {
        $filter = CurrencyFilter::new('currency')
            ->includeOnly(['EUR', 'USD', 'GBP'])
            ->preferredChoices(['EUR'])
            ->canSelectMultiple()
            ->renderExpanded();

        $dto = $filter->getAsDto();

        $this->assertSame(['EUR', 'USD', 'GBP'], $dto->getCustomOption(CurrencyFilter::OPTION_CURRENCY_CODES_TO_KEEP));
        $this->assertSame(['EUR'], $dto->getCustomOption(CurrencyFilter::OPTION_PREFERRED_CHOICES));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }
}
