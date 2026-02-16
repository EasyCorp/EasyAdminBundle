<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\LanguageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\LanguageFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Languages;

class LanguageConfiguratorTest extends TestCase
{
    private LanguageConfigurator $configurator;
    private EntityDto $entityDto;
    private AdminContext $adminContext;

    protected function setUp(): void
    {
        $this->configurator = new LanguageConfigurator();

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

    public function testSupportsLanguageFilter(): void
    {
        $filter = LanguageFilter::new('language');
        $filterDto = $filter->getAsDto();

        $this->assertTrue($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testDoesNotSupportOtherFilters(): void
    {
        $filter = TextFilter::new('name');
        $filterDto = $filter->getAsDto();

        $this->assertFalse($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testConfigureSetsAllLanguages(): void
    {
        $filter = LanguageFilter::new('language');
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');
        $expectedLanguages = array_flip(Languages::getNames());

        $this->assertSame($expectedLanguages, $choices);
        $this->assertFalse($filterDto->getFormTypeOption('value_type_options.choice_translation_domain'));
    }

    public function testConfigureWithIncludeOnly(): void
    {
        $filter = LanguageFilter::new('language')->includeOnly(['en', 'es', 'fr']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertCount(3, $choices);
        $this->assertArrayHasKey(Languages::getName('en'), $choices);
        $this->assertArrayHasKey(Languages::getName('es'), $choices);
        $this->assertArrayHasKey(Languages::getName('fr'), $choices);
        $this->assertSame('en', $choices[Languages::getName('en')]);
    }

    public function testConfigureWithRemove(): void
    {
        $filter = LanguageFilter::new('language')->remove(['en', 'es']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertArrayNotHasKey(Languages::getName('en'), $choices);
        $this->assertArrayNotHasKey(Languages::getName('es'), $choices);
        $this->assertArrayHasKey(Languages::getName('fr'), $choices);
    }

    public function testConfigureWithPreferredChoices(): void
    {
        $filter = LanguageFilter::new('language')->preferredChoices(['en', 'es']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $preferredChoices = $filterDto->getFormTypeOption('value_type_options.preferred_choices');

        $this->assertSame(['en', 'es'], $preferredChoices);
    }

    public function testConfigureWithAlpha3Codes(): void
    {
        $filter = LanguageFilter::new('language')->useAlpha3Codes()->includeOnly(['eng', 'spa']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertCount(2, $choices);
        $this->assertSame('eng', $choices[Languages::getAlpha3Name('eng')]);
        $this->assertSame('spa', $choices[Languages::getAlpha3Name('spa')]);
    }
}
