<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\MoneyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class MoneyFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $intlFormatterMock = $this->getMockBuilder(IntlFormatter::class)
            ->disableOriginalConstructor()
            ->setMethods(['formatCurrency'])
            ->getMock();
        $intlFormatterMock->method('formatCurrency')->willReturnCallback(
            function ($value) { return $value.'€'; }
        );

        $propertyAccessorMock = $this->getMockBuilder(PropertyAccessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['isReadable', 'getValue'])
            ->getMock();
        $propertyAccessorMock->method('isReadable')->willReturn(true);
        $propertyAccessorMock->method('getValue')->willReturn('USD');

        $this->configurator = new MoneyConfigurator($intlFormatterMock, $propertyAccessorMock);
    }

    public function testFieldWithoutCurrency()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = MoneyField::new('foo')->setValue(100);
        $this->configure($field);
    }

    public function testFieldWithNullValues()
    {
        $field = MoneyField::new('foo')->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(MoneyField::OPTION_CURRENCY));
    }

    public function testFieldWithWrongCurrency()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = MoneyField::new('foo')->setValue(100)->setCurrency('THIS_DOES_NOT_EXIST');
        $this->configure($field);
    }

    public function testFieldWithHardcodedCurrency()
    {
        $field = MoneyField::new('foo')->setValue(100)->setCurrency('EUR');
        $fieldDto = $this->configure($field);

        self::assertSame('EUR', $fieldDto->getCustomOption(MoneyField::OPTION_CURRENCY));
        self::assertSame('EUR', $fieldDto->getFormTypeOption('currency'));
    }

    public function testFieldWithPropertyPathCurrency()
    {
        $field = MoneyField::new('foo')->setValue(100)->setCurrencyPropertyPath('bar');
        $fieldDto = $this->configure($field);

        self::assertSame('USD', $fieldDto->getFormTypeOption('currency'));
    }

    public function testFieldDecimals()
    {
        $field = MoneyField::new('foo')->setValue(100)->setCurrency('EUR');
        $fieldDto = $this->configure($field);
        self::assertSame(2, $fieldDto->getCustomOption('numDecimals'));
        self::assertSame(2, $fieldDto->getFormTypeOption('scale'));

        $field->setNumDecimals(3);
        $fieldDto = $this->configure($field);
        self::assertSame(3, $fieldDto->getCustomOption('numDecimals'));
        self::assertSame(3, $fieldDto->getFormTypeOption('scale'));
    }

    public function testFieldCents()
    {
        $field = MoneyField::new('foo')->setValue(100)->setCurrency('EUR');
        $fieldDto = $this->configure($field);
        self::assertSame('1€', $fieldDto->getFormattedValue());
        self::assertSame(100, $fieldDto->getFormTypeOption('divisor'));

        $field->setStoredAsCents(false);
        $fieldDto = $this->configure($field);
        self::assertSame('100€', $fieldDto->getFormattedValue());
        self::assertSame(1, $fieldDto->getFormTypeOption('divisor'));
    }
}
