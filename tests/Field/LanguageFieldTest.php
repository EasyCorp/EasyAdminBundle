<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\LanguageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;

class LanguageFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new LanguageConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = LanguageField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(LanguageField::OPTION_SHOW_CODE));
        self::assertTrue($fieldDto->getCustomOption(LanguageField::OPTION_SHOW_NAME));
        self::assertSame(LanguageField::FORMAT_ISO_639_ALPHA2, $fieldDto->getCustomOption(LanguageField::OPTION_LANGUAGE_CODE_FORMAT));
        self::assertNull($fieldDto->getCustomOption(LanguageField::OPTION_LANGUAGE_CODES_TO_KEEP));
        self::assertNull($fieldDto->getCustomOption(LanguageField::OPTION_LANGUAGE_CODES_TO_REMOVE));
        self::assertSame('ea-autocomplete', $fieldDto->getFormTypeOption('attr.data-ea-widget'));
        self::assertSame(LanguageType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-language', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValue(): void
    {
        $field = LanguageField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithValidLanguageCode(): void
    {
        $field = LanguageField::new('foo');
        $field->setValue('en');
        $fieldDto = $this->configure($field);

        self::assertSame('en', $fieldDto->getValue());
        self::assertSame('English', $fieldDto->getFormattedValue());
    }

    public function testFieldWithSpanishLanguageCode(): void
    {
        $field = LanguageField::new('foo');
        $field->setValue('es');
        $fieldDto = $this->configure($field);

        self::assertSame('es', $fieldDto->getValue());
        self::assertSame('Spanish', $fieldDto->getFormattedValue());
    }

    public function testFieldWithInvalidLanguageCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = LanguageField::new('foo');
        $field->setValue('invalid');
        $this->configure($field);
    }

    public function testShowCode(): void
    {
        $field = LanguageField::new('foo');
        $field->showCode();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(LanguageField::OPTION_SHOW_CODE));
    }

    public function testHideCode(): void
    {
        $field = LanguageField::new('foo');
        $field->showCode(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(LanguageField::OPTION_SHOW_CODE));
    }

    public function testShowName(): void
    {
        $field = LanguageField::new('foo');
        $field->showName();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(LanguageField::OPTION_SHOW_NAME));
    }

    public function testHideName(): void
    {
        $field = LanguageField::new('foo');
        $field->showName(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(LanguageField::OPTION_SHOW_NAME));
    }

    public function testUseAlpha3Codes(): void
    {
        $field = LanguageField::new('foo');
        $field->useAlpha3Codes();
        $fieldDto = $this->configure($field);

        self::assertSame(LanguageField::FORMAT_ISO_639_ALPHA3, $fieldDto->getCustomOption(LanguageField::OPTION_LANGUAGE_CODE_FORMAT));
    }

    public function testUseAlpha2Codes(): void
    {
        $field = LanguageField::new('foo');
        $field->useAlpha3Codes(false);
        $fieldDto = $this->configure($field);

        self::assertSame(LanguageField::FORMAT_ISO_639_ALPHA2, $fieldDto->getCustomOption(LanguageField::OPTION_LANGUAGE_CODE_FORMAT));
    }

    public function testFieldWithAlpha3LanguageCode(): void
    {
        $field = LanguageField::new('foo');
        $field->setValue('eng');
        $field->useAlpha3Codes();
        $fieldDto = $this->configure($field);

        self::assertSame('eng', $fieldDto->getValue());
        self::assertSame('English', $fieldDto->getFormattedValue());
    }

    public function testIncludeOnly(): void
    {
        $field = LanguageField::new('foo');
        $field->includeOnly(['en', 'es', 'fr']);
        $fieldDto = $this->configure($field, Crud::PAGE_EDIT);
        $choices = $fieldDto->getFormTypeOption('choices');

        self::assertSame(['en', 'es', 'fr'], $fieldDto->getCustomOption(LanguageField::OPTION_LANGUAGE_CODES_TO_KEEP));
        self::assertCount(3, $choices);
        self::assertContains('en', $choices);
        self::assertContains('es', $choices);
        self::assertContains('fr', $choices);
    }

    public function testRemove(): void
    {
        $field = LanguageField::new('foo');
        $field->remove(['en', 'es']);
        $fieldDto = $this->configure($field, Crud::PAGE_EDIT);
        $choices = $fieldDto->getFormTypeOption('choices');

        self::assertSame(['en', 'es'], $fieldDto->getCustomOption(LanguageField::OPTION_LANGUAGE_CODES_TO_REMOVE));
        self::assertNotContains('en', $choices);
        self::assertNotContains('es', $choices);
    }

    public function testFormTypeOptionsOnFormPages(): void
    {
        $field = LanguageField::new('foo');
        $fieldDto = $this->configure($field, Crud::PAGE_EDIT);

        self::assertNotNull($fieldDto->getFormTypeOption('choices'));
        self::assertNull($fieldDto->getFormTypeOption('choice_loader'));
        self::assertFalse($fieldDto->getFormTypeOption('choice_translation_domain'));
    }
}
