<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Filter\LocaleFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use PHPUnit\Framework\TestCase;

class LocaleFilterTest extends TestCase
{
    public function testNew(): void
    {
        $filter = LocaleFilter::new('locale');
        $dto = $filter->getAsDto();

        $this->assertSame('locale', $dto->getProperty());
        $this->assertSame(LocaleFilter::class, $dto->getFqcn());
        $this->assertSame(ChoiceFilterType::class, $dto->getFormType());
    }

    public function testNewWithLabel(): void
    {
        $filter = LocaleFilter::new('locale', 'Locale Label');
        $dto = $filter->getAsDto();

        $this->assertSame('Locale Label', $dto->getLabel());
    }

    public function testIncludeOnly(): void
    {
        $filter = LocaleFilter::new('locale')->includeOnly(['en_US', 'es_ES', 'fr_FR']);
        $dto = $filter->getAsDto();

        $this->assertSame(['en_US', 'es_ES', 'fr_FR'], $dto->getCustomOption(LocaleFilter::OPTION_LOCALE_CODES_TO_KEEP));
    }

    public function testRemove(): void
    {
        $filter = LocaleFilter::new('locale')->remove(['de_DE', 'it_IT']);
        $dto = $filter->getAsDto();

        $this->assertSame(['de_DE', 'it_IT'], $dto->getCustomOption(LocaleFilter::OPTION_LOCALE_CODES_TO_REMOVE));
    }

    public function testPreferredChoices(): void
    {
        $filter = LocaleFilter::new('locale')->preferredChoices(['en_US', 'es_ES']);
        $dto = $filter->getAsDto();

        $this->assertSame(['en_US', 'es_ES'], $dto->getCustomOption(LocaleFilter::OPTION_PREFERRED_CHOICES));
    }

    public function testCanSelectMultiple(): void
    {
        $filter = LocaleFilter::new('locale')->canSelectMultiple();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
    }

    public function testRenderExpanded(): void
    {
        $filter = LocaleFilter::new('locale')->renderExpanded();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }

    public function testFluentInterface(): void
    {
        $filter = LocaleFilter::new('locale')
            ->includeOnly(['en_US', 'es_ES', 'fr_FR'])
            ->preferredChoices(['en_US'])
            ->canSelectMultiple()
            ->renderExpanded();

        $dto = $filter->getAsDto();

        $this->assertSame(['en_US', 'es_ES', 'fr_FR'], $dto->getCustomOption(LocaleFilter::OPTION_LOCALE_CODES_TO_KEEP));
        $this->assertSame(['en_US'], $dto->getCustomOption(LocaleFilter::OPTION_PREFERRED_CHOICES));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }
}
