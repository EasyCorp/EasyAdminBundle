<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\PercentConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;

class PercentFieldTest extends AbstractFieldTest
{
    private $intlFormatterMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->intlFormatterMock = $this->getMockBuilder(IntlFormatter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['formatNumber'])
            ->getMock();

        $this->configurator = new PercentConfigurator($this->intlFormatterMock);
    }

    public function testFieldWithNullValues()
    {
        $field = PercentField::new('foo')->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getFormattedValue());
        self::assertSame('%', $fieldDto->getCustomOption(PercentField::OPTION_SYMBOL));
    }

    public function testFieldDefaultDecimalsAndFractional()
    {
        $this->intlFormatterMock->method('formatNumber')->with(100.9874)->willReturn('100');

        $field = PercentField::new('foo')->setValue(100.9874)->setStoredAsFractional(false);
        $fieldDto = $this->configure($field);
        self::assertSame(0, $fieldDto->getCustomOption(PercentField::OPTION_NUM_DECIMALS));
        self::assertSame(0, $fieldDto->getFormTypeOption('scale'));
        self::assertSame('100%', $fieldDto->getFormattedValue());
    }

    public function testFieldDecimalsAndFractional()
    {
        $this->intlFormatterMock->method('formatNumber')->with(100.1345)->willReturn('100.134');

        $field = PercentField::new('foo')->setValue(100.1345)->setStoredAsFractional(false)->setNumDecimals(3);
        $fieldDto = $this->configure($field);
        self::assertSame(3, $fieldDto->getCustomOption(PercentField::OPTION_NUM_DECIMALS));
        self::assertSame(3, $fieldDto->getFormTypeOption('scale'));
        self::assertSame('100.134%', $fieldDto->getFormattedValue());
    }

    public function testFieldSynmbolAndFractional()
    {
        $this->intlFormatterMock->method('formatNumber')->with(100)->willReturn('100');

        $field = PercentField::new('foo')->setValue(100)->setSymbol(' %')->setStoredAsFractional(false);
        $fieldDto = $this->configure($field);
        self::assertSame('100 %', $fieldDto->getFormattedValue());
        self::assertSame('integer', $fieldDto->getFormTypeOption('type'));
    }
}
