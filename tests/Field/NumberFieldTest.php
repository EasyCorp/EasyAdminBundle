<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\NumberConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class NumberFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new NumberConfigurator(new IntlFormatter());
    }

    public function testDefaultOptions(): void
    {
        $field = NumberField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(NumberField::OPTION_NUM_DECIMALS));
        self::assertSame(\NumberFormatter::ROUND_HALFUP, $fieldDto->getCustomOption(NumberField::OPTION_ROUNDING_MODE));
        self::assertFalse($fieldDto->getCustomOption(NumberField::OPTION_STORED_AS_STRING));
        self::assertNull($fieldDto->getCustomOption(NumberField::OPTION_NUMBER_FORMAT));
        self::assertNull($fieldDto->getCustomOption(NumberField::OPTION_THOUSANDS_SEPARATOR));
        self::assertNull($fieldDto->getCustomOption(NumberField::OPTION_DECIMAL_SEPARATOR));
        self::assertSame(NumberType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-number', $fieldDto->getCssClass());
    }

    public function testFormTypeOptions(): void
    {
        $field = NumberField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame('number', $fieldDto->getFormTypeOption('input'));
        self::assertSame(\NumberFormatter::ROUND_HALFUP, $fieldDto->getFormTypeOption('rounding_mode'));
    }

    public function testFieldWithNullValue(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithFloatValue(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(123.456);
        $fieldDto = $this->configure($field);

        self::assertSame(123.456, $fieldDto->getValue());
    }

    public function testSetNumDecimals(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(123.456789);
        $field->setNumDecimals(2);
        $fieldDto = $this->configure($field);

        self::assertSame(2, $fieldDto->getCustomOption(NumberField::OPTION_NUM_DECIMALS));
        self::assertSame(2, $fieldDto->getFormTypeOption('scale'));
        self::assertMatchesRegularExpression('/123[.,]46/', $fieldDto->getFormattedValue());
    }

    public function testSetNumDecimalsThrowsExceptionForNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        NumberField::new('foo')->setNumDecimals(-1);
    }

    /**
     * @testWith [0, "ROUND_DOWN"]
     *           [1, "ROUND_FLOOR"]
     *           [2, "ROUND_UP"]
     *           [3, "ROUND_CEILING"]
     *           [5, "ROUND_HALF_DOWN"]
     *           [6, "ROUND_HALF_EVEN"]
     *           [4, "ROUND_HALF_UP"]
     */
    public function testSetRoundingMode(int $mode, string $modeName): void
    {
        $field = NumberField::new('foo');
        $field->setRoundingMode($mode);
        $fieldDto = $this->configure($field);

        self::assertSame($mode, $fieldDto->getCustomOption(NumberField::OPTION_ROUNDING_MODE));
        self::assertSame($mode, $fieldDto->getFormTypeOption('rounding_mode'));
    }

    public function testSetRoundingModeThrowsExceptionForInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        NumberField::new('foo')->setRoundingMode(999);
    }

    public function testSetStoredAsString(): void
    {
        $field = NumberField::new('foo');
        $field->setStoredAsString(true);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(NumberField::OPTION_STORED_AS_STRING));
        self::assertSame('string', $fieldDto->getFormTypeOption('input'));
    }

    public function testSetStoredAsStringFalse(): void
    {
        $field = NumberField::new('foo');
        $field->setStoredAsString(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(NumberField::OPTION_STORED_AS_STRING));
        self::assertSame('number', $fieldDto->getFormTypeOption('input'));
    }

    public function testSetNumberFormat(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(42.5);
        $field->setNumberFormat('%.3f');
        $fieldDto = $this->configure($field);

        self::assertSame('%.3f', $fieldDto->getCustomOption(NumberField::OPTION_NUMBER_FORMAT));
        self::assertSame('42.500', $fieldDto->getFormattedValue());
    }

    public function testSetThousandsSeparator(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(1234567.89);
        $field->setThousandsSeparator(' ');
        $fieldDto = $this->configure($field);

        self::assertSame(' ', $fieldDto->getCustomOption(NumberField::OPTION_THOUSANDS_SEPARATOR));
        self::assertStringContainsString('1 234 567', $fieldDto->getFormattedValue());
    }

    public function testSetDecimalSeparator(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(123.45);
        $field->setDecimalSeparator(',');
        $field->setNumDecimals(2);
        $fieldDto = $this->configure($field);

        self::assertSame(',', $fieldDto->getCustomOption(NumberField::OPTION_DECIMAL_SEPARATOR));
        self::assertStringContainsString('123,45', $fieldDto->getFormattedValue());
    }

    public function testThousandsAndDecimalSeparatorsTogether(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(1234567.89);
        $field->setThousandsSeparator('.');
        $field->setDecimalSeparator(',');
        $field->setNumDecimals(2);
        $fieldDto = $this->configure($field);

        self::assertSame('1.234.567,89', $fieldDto->getFormattedValue());
    }

    public function testFieldWithZeroValue(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(0);
        $fieldDto = $this->configure($field);

        self::assertSame(0, $fieldDto->getValue());
    }

    public function testFieldWithNegativeValue(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(-123.45);
        $fieldDto = $this->configure($field);

        self::assertSame(-123.45, $fieldDto->getValue());
    }

    public function testRoundingBehaviorHalfUp(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(123.455);
        $field->setNumDecimals(2);
        $field->setRoundingMode(\NumberFormatter::ROUND_HALFUP);
        $fieldDto = $this->configure($field);

        self::assertMatchesRegularExpression('/123[.,]46/', $fieldDto->getFormattedValue());
    }

    public function testRoundingBehaviorDown(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(123.459);
        $field->setNumDecimals(2);
        $field->setRoundingMode(\NumberFormatter::ROUND_DOWN);
        $fieldDto = $this->configure($field);

        self::assertMatchesRegularExpression('/123[.,]45/', $fieldDto->getFormattedValue());
    }

    public function testRoundingBehaviorUp(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(123.451);
        $field->setNumDecimals(2);
        $field->setRoundingMode(\NumberFormatter::ROUND_UP);
        $fieldDto = $this->configure($field);

        self::assertMatchesRegularExpression('/123[.,]46/', $fieldDto->getFormattedValue());
    }

    public function testNumberFormatTakesPrecedenceOverSeparators(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(1234.5);
        $field->setNumberFormat('%08.2f');
        $field->setThousandsSeparator(' ');
        $field->setDecimalSeparator(',');
        $fieldDto = $this->configure($field);

        // when numberFormat is set, it takes precedence over separators
        // sprintf('%08.2f', 1234.5) = '01234.50'
        self::assertSame('01234.50', $fieldDto->getFormattedValue());
    }

    public function testFormattedValueWithLargeNumber(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(9876543210.12);
        $field->setThousandsSeparator(',');
        $field->setNumDecimals(2);
        $fieldDto = $this->configure($field);

        self::assertStringContainsString('9,876,543,210', $fieldDto->getFormattedValue());
    }

    public function testFormattedValueWithSmallDecimal(): void
    {
        $field = NumberField::new('foo');
        $field->setValue(0.00123);
        $field->setNumDecimals(5);
        $fieldDto = $this->configure($field);

        self::assertMatchesRegularExpression('/0[.,]00123/', $fieldDto->getFormattedValue());
    }
}
