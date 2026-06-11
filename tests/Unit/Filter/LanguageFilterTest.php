<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Filter\LanguageFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use PHPUnit\Framework\TestCase;

class LanguageFilterTest extends TestCase
{
    public function testNew(): void
    {
        $filter = LanguageFilter::new('language');
        $dto = $filter->getAsDto();

        $this->assertSame('language', $dto->getProperty());
        $this->assertSame(LanguageFilter::class, $dto->getFqcn());
        $this->assertSame(ChoiceFilterType::class, $dto->getFormType());
    }

    public function testNewWithLabel(): void
    {
        $filter = LanguageFilter::new('language', 'Language Label');
        $dto = $filter->getAsDto();

        $this->assertSame('Language Label', $dto->getLabel());
    }

    public function testIncludeOnly(): void
    {
        $filter = LanguageFilter::new('language')->includeOnly(['en', 'es', 'fr']);
        $dto = $filter->getAsDto();

        $this->assertSame(['en', 'es', 'fr'], $dto->getCustomOption(LanguageFilter::OPTION_LANGUAGE_CODES_TO_KEEP));
    }

    public function testRemove(): void
    {
        $filter = LanguageFilter::new('language')->remove(['de', 'it']);
        $dto = $filter->getAsDto();

        $this->assertSame(['de', 'it'], $dto->getCustomOption(LanguageFilter::OPTION_LANGUAGE_CODES_TO_REMOVE));
    }

    public function testPreferredChoices(): void
    {
        $filter = LanguageFilter::new('language')->preferredChoices(['en', 'es']);
        $dto = $filter->getAsDto();

        $this->assertSame(['en', 'es'], $dto->getCustomOption(LanguageFilter::OPTION_PREFERRED_CHOICES));
    }

    public function testUseAlpha3Codes(): void
    {
        $filter = LanguageFilter::new('language')->useAlpha3Codes();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getCustomOption(LanguageFilter::OPTION_USE_ALPHA3_CODES));
    }

    public function testUseAlpha3CodesFalse(): void
    {
        $filter = LanguageFilter::new('language')->useAlpha3Codes(false);
        $dto = $filter->getAsDto();

        $this->assertFalse($dto->getCustomOption(LanguageFilter::OPTION_USE_ALPHA3_CODES));
    }

    public function testCanSelectMultiple(): void
    {
        $filter = LanguageFilter::new('language')->canSelectMultiple();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
    }

    public function testRenderExpanded(): void
    {
        $filter = LanguageFilter::new('language')->renderExpanded();
        $dto = $filter->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }

    public function testFluentInterface(): void
    {
        $filter = LanguageFilter::new('language')
            ->includeOnly(['en', 'es', 'fr'])
            ->preferredChoices(['en'])
            ->useAlpha3Codes()
            ->canSelectMultiple()
            ->renderExpanded();

        $dto = $filter->getAsDto();

        $this->assertSame(['en', 'es', 'fr'], $dto->getCustomOption(LanguageFilter::OPTION_LANGUAGE_CODES_TO_KEEP));
        $this->assertSame(['en'], $dto->getCustomOption(LanguageFilter::OPTION_PREFERRED_CHOICES));
        $this->assertTrue($dto->getCustomOption(LanguageFilter::OPTION_USE_ALPHA3_CODES));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.multiple'));
        $this->assertTrue($dto->getFormTypeOption('value_type_options.expanded'));
    }
}
