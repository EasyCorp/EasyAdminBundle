<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\LocaleConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;

class LocaleFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new LocaleConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = LocaleField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(LocaleField::OPTION_SHOW_CODE));
        self::assertTrue($fieldDto->getCustomOption(LocaleField::OPTION_SHOW_NAME));
        self::assertNull($fieldDto->getCustomOption(LocaleField::OPTION_LOCALE_CODES_TO_KEEP));
        self::assertNull($fieldDto->getCustomOption(LocaleField::OPTION_LOCALE_CODES_TO_REMOVE));
        self::assertSame('ea-autocomplete', $fieldDto->getFormTypeOption('attr.data-ea-widget'));
        self::assertSame(LocaleType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-locale', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValue(): void
    {
        $field = LocaleField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithValidLocaleCode(): void
    {
        $field = LocaleField::new('foo');
        $field->setValue('en');
        $fieldDto = $this->configure($field);

        self::assertSame('en', $fieldDto->getValue());
        self::assertSame('English', $fieldDto->getFormattedValue());
    }

    public function testFieldWithLocaleCodeWithCountry(): void
    {
        $field = LocaleField::new('foo');
        $field->setValue('en_US');
        $fieldDto = $this->configure($field);

        self::assertSame('en_US', $fieldDto->getValue());
        self::assertSame('English (United States)', $fieldDto->getFormattedValue());
    }

    public function testFieldWithSpanishLocale(): void
    {
        $field = LocaleField::new('foo');
        $field->setValue('es_ES');
        $fieldDto = $this->configure($field);

        self::assertSame('es_ES', $fieldDto->getValue());
        self::assertSame('Spanish (Spain)', $fieldDto->getFormattedValue());
    }

    public function testFieldWithInvalidLocaleCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = LocaleField::new('foo');
        $field->setValue('invalid_code');
        $this->configure($field);
    }

    public function testShowCode(): void
    {
        $field = LocaleField::new('foo');
        $field->showCode();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(LocaleField::OPTION_SHOW_CODE));
    }

    public function testHideCode(): void
    {
        $field = LocaleField::new('foo');
        $field->showCode(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(LocaleField::OPTION_SHOW_CODE));
    }

    public function testShowName(): void
    {
        $field = LocaleField::new('foo');
        $field->showName();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(LocaleField::OPTION_SHOW_NAME));
    }

    public function testHideName(): void
    {
        $field = LocaleField::new('foo');
        $field->showName(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(LocaleField::OPTION_SHOW_NAME));
    }

    public function testIncludeOnly(): void
    {
        $field = LocaleField::new('foo');
        $field->includeOnly(['en', 'es', 'fr']);
        $fieldDto = $this->configure($field, Crud::PAGE_EDIT);
        $choices = $fieldDto->getFormTypeOption('choices');

        self::assertSame(['en', 'es', 'fr'], $fieldDto->getCustomOption(LocaleField::OPTION_LOCALE_CODES_TO_KEEP));
        self::assertCount(3, $choices);
        self::assertContains('en', $choices);
        self::assertContains('es', $choices);
        self::assertContains('fr', $choices);
    }

    public function testRemove(): void
    {
        $field = LocaleField::new('foo');
        $field->remove(['en', 'es']);
        $fieldDto = $this->configure($field, Crud::PAGE_EDIT);
        $choices = $fieldDto->getFormTypeOption('choices');

        self::assertSame(['en', 'es'], $fieldDto->getCustomOption(LocaleField::OPTION_LOCALE_CODES_TO_REMOVE));
        self::assertNotContains('en', $choices);
        self::assertNotContains('es', $choices);
    }

    public function testFormTypeOptionsOnFormPages(): void
    {
        $field = LocaleField::new('foo');
        $fieldDto = $this->configure($field, Crud::PAGE_EDIT);

        self::assertNotNull($fieldDto->getFormTypeOption('choices'));
        self::assertNull($fieldDto->getFormTypeOption('choice_loader'));
        self::assertFalse($fieldDto->getFormTypeOption('choice_translation_domain'));
    }
}
