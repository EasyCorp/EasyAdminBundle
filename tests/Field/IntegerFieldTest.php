<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\IntegerConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class IntegerFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new IntegerConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = IntegerField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(IntegerField::OPTION_NUMBER_FORMAT));
        self::assertNull($fieldDto->getCustomOption(IntegerField::OPTION_THOUSANDS_SEPARATOR));
        self::assertSame(IntegerType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-integer', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValue(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
        self::assertNull($fieldDto->getFormattedValue());
    }

    public function testFieldWithIntegerValue(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(12345);
        $fieldDto = $this->configure($field);

        self::assertSame(12345, $fieldDto->getValue());
        self::assertSame(12345, $fieldDto->getFormattedValue());
    }

    public function testFieldWithNegativeValue(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(-99);
        $fieldDto = $this->configure($field);

        self::assertSame(-99, $fieldDto->getValue());
    }

    public function testSetNumberFormat(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(42);
        $field->setNumberFormat('%05d');
        $fieldDto = $this->configure($field);

        self::assertSame('%05d', $fieldDto->getCustomOption(IntegerField::OPTION_NUMBER_FORMAT));
        self::assertSame('00042', $fieldDto->getFormattedValue());
    }

    public function testSetThousandsSeparator(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(1234567);
        $field->setThousandsSeparator(',');
        $fieldDto = $this->configure($field);

        self::assertSame(',', $fieldDto->getCustomOption(IntegerField::OPTION_THOUSANDS_SEPARATOR));
        self::assertSame('1,234,567', $fieldDto->getFormattedValue());
    }

    public function testSetThousandsSeparatorWithDifferentSeparator(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(1234567);
        $field->setThousandsSeparator(' ');
        $fieldDto = $this->configure($field);

        self::assertSame(' ', $fieldDto->getCustomOption(IntegerField::OPTION_THOUSANDS_SEPARATOR));
        self::assertSame('1 234 567', $fieldDto->getFormattedValue());
    }

    public function testNumberFormatTakesPrecedenceOverThousandsSeparator(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(1234567);
        $field->setNumberFormat('%08d');
        $field->setThousandsSeparator(',');
        $fieldDto = $this->configure($field);

        self::assertSame('01234567', $fieldDto->getFormattedValue());
    }

    public function testFieldWithZeroValue(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(0);
        $fieldDto = $this->configure($field);

        self::assertSame(0, $fieldDto->getValue());
        self::assertSame(0, $fieldDto->getFormattedValue());
    }

    public function testFormattedValueWithLargeNumber(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(9876543210);
        $field->setThousandsSeparator(',');
        $fieldDto = $this->configure($field);

        self::assertSame('9,876,543,210', $fieldDto->getFormattedValue());
    }

    public function testFormattedValueWithNegativeAndThousandsSeparator(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(-1234567);
        $field->setThousandsSeparator(',');
        $fieldDto = $this->configure($field);

        self::assertSame('-1,234,567', $fieldDto->getFormattedValue());
    }

    public function testThousandsSeparatorWithDot(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(1234567);
        $field->setThousandsSeparator('.');
        $fieldDto = $this->configure($field);

        self::assertSame('1.234.567', $fieldDto->getFormattedValue());
    }

    public function testNumberFormatWithHexadecimal(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(255);
        $field->setNumberFormat('0x%02X');
        $fieldDto = $this->configure($field);

        self::assertSame('0xFF', $fieldDto->getFormattedValue());
    }

    public function testSmallNumberWithThousandsSeparator(): void
    {
        $field = IntegerField::new('foo');
        $field->setValue(999);
        $field->setThousandsSeparator(',');
        $fieldDto = $this->configure($field);

        self::assertSame('999', $fieldDto->getFormattedValue());
    }
}
