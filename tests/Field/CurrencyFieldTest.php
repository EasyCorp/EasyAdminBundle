<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CurrencyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;

class CurrencyFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new CurrencyConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = CurrencyField::new('currency');
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_CODE));
        self::assertTrue($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_NAME));
        self::assertTrue($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_SYMBOL));
        self::assertSame('ea-autocomplete', $fieldDto->getFormTypeOption('attr.data-ea-widget'));
        self::assertFalse($fieldDto->getFormTypeOption('choice_translation_domain'));
        self::assertSame(CurrencyType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-currency', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValue(): void
    {
        $field = CurrencyField::new('currency');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithValidCurrencyCode(): void
    {
        $field = CurrencyField::new('currency');
        $field->setValue('USD');
        $fieldDto = $this->configure($field);

        self::assertSame('USD', $fieldDto->getValue());
        self::assertIsArray($fieldDto->getFormattedValue());
        self::assertSame('US Dollar', $fieldDto->getFormattedValue()['name']);
        self::assertSame('$', $fieldDto->getFormattedValue()['symbol']);
    }

    public function testFieldWithEurCurrency(): void
    {
        $field = CurrencyField::new('currency');
        $field->setValue('EUR');
        $fieldDto = $this->configure($field);

        self::assertSame('EUR', $fieldDto->getValue());
        self::assertIsArray($fieldDto->getFormattedValue());
        self::assertSame('Euro', $fieldDto->getFormattedValue()['name']);
        self::assertSame('€', $fieldDto->getFormattedValue()['symbol']);
    }

    public function testFieldWithInvalidCurrencyCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = CurrencyField::new('currency');
        $field->setValue('INVALID');
        $this->configure($field);
    }

    public function testShowCode(): void
    {
        $field = CurrencyField::new('currency');
        $field->showCode();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_CODE));
    }

    public function testHideCode(): void
    {
        $field = CurrencyField::new('currency');
        $field->showCode(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_CODE));
    }

    public function testShowName(): void
    {
        $field = CurrencyField::new('currency');
        $field->showName();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_NAME));
    }

    public function testHideName(): void
    {
        $field = CurrencyField::new('currency');
        $field->showName(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_NAME));
    }

    public function testShowSymbol(): void
    {
        $field = CurrencyField::new('currency');
        $field->showSymbol();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_SYMBOL));
    }

    public function testHideSymbol(): void
    {
        $field = CurrencyField::new('currency');
        $field->showSymbol(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CurrencyField::OPTION_SHOW_SYMBOL));
    }

    public function testFormTypeOptionsAreNotOverriddenIfAlreadySet(): void
    {
        $field = CurrencyField::new('currency');
        $field->setFormTypeOption('attr.data-ea-widget', 'custom-widget');
        $field->setFormTypeOption('choice_translation_domain', 'messages');
        $fieldDto = $this->configure($field);

        self::assertSame('custom-widget', $fieldDto->getFormTypeOption('attr.data-ea-widget'));
        self::assertSame('messages', $fieldDto->getFormTypeOption('choice_translation_domain'));
    }
}
