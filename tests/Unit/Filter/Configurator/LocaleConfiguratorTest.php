<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\LocaleConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\LocaleFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Locales;

class LocaleConfiguratorTest extends TestCase
{
    private LocaleConfigurator $configurator;
    private EntityDto $entityDto;
    private AdminContext $adminContext;

    protected function setUp(): void
    {
        $this->configurator = new LocaleConfigurator();

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

    public function testSupportsLocaleFilter(): void
    {
        $filter = LocaleFilter::new('locale');
        $filterDto = $filter->getAsDto();

        $this->assertTrue($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testDoesNotSupportOtherFilters(): void
    {
        $filter = TextFilter::new('name');
        $filterDto = $filter->getAsDto();

        $this->assertFalse($this->configurator->supports($filterDto, null, $this->entityDto, $this->adminContext));
    }

    public function testConfigureSetsAllLocales(): void
    {
        $filter = LocaleFilter::new('locale');
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');
        $expectedLocales = array_flip(Locales::getNames());

        $this->assertSame($expectedLocales, $choices);
        $this->assertFalse($filterDto->getFormTypeOption('value_type_options.choice_translation_domain'));
    }

    public function testConfigureWithIncludeOnly(): void
    {
        $filter = LocaleFilter::new('locale')->includeOnly(['en_US', 'es_ES', 'fr_FR']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertCount(3, $choices);
        $this->assertArrayHasKey(Locales::getName('en_US'), $choices);
        $this->assertArrayHasKey(Locales::getName('es_ES'), $choices);
        $this->assertArrayHasKey(Locales::getName('fr_FR'), $choices);
        $this->assertSame('en_US', $choices[Locales::getName('en_US')]);
    }

    public function testConfigureWithRemove(): void
    {
        $filter = LocaleFilter::new('locale')->remove(['en_US', 'es_ES']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        $this->assertArrayNotHasKey(Locales::getName('en_US'), $choices);
        $this->assertArrayNotHasKey(Locales::getName('es_ES'), $choices);
        $this->assertArrayHasKey(Locales::getName('fr_FR'), $choices);
    }

    public function testConfigureWithPreferredChoices(): void
    {
        $filter = LocaleFilter::new('locale')->preferredChoices(['en_US', 'es_ES']);
        $filterDto = $filter->getAsDto();

        $this->configurator->configure($filterDto, null, $this->entityDto, $this->adminContext);

        $preferredChoices = $filterDto->getFormTypeOption('value_type_options.preferred_choices');

        $this->assertSame(['en_US', 'es_ES'], $preferredChoices);
    }
}
