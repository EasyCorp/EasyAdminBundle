<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\PercentConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use Symfony\Component\Form\Extension\Core\Type\PercentType;

class PercentFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new PercentConfigurator(new IntlFormatter());
    }

    public function testDefaultOptions(): void
    {
        $field = PercentField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame(0, $fieldDto->getCustomOption(PercentField::OPTION_NUM_DECIMALS));
        self::assertTrue($fieldDto->getCustomOption(PercentField::OPTION_STORED_AS_FRACTIONAL));
        self::assertSame('%', $fieldDto->getCustomOption(PercentField::OPTION_SYMBOL));
        self::assertSame(\NumberFormatter::ROUND_HALFUP, $fieldDto->getCustomOption(PercentField::OPTION_ROUNDING_MODE));
        self::assertSame(PercentType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-percent', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValues(): void
    {
        $field = PercentField::new('foo')->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getFormattedValue());
        self::assertSame('%', $fieldDto->getCustomOption(PercentField::OPTION_SYMBOL));
    }

    public function testFieldDefaultDecimalsAndFractional(): void
    {
        $field = PercentField::new('foo')->setValue(100.9874)->setStoredAsFractional(false);
        $fieldDto = $this->configure($field);

        self::assertSame(0, $fieldDto->getCustomOption(PercentField::OPTION_NUM_DECIMALS));
        self::assertSame(0, $fieldDto->getFormTypeOption('scale'));
        self::assertSame('101%', $fieldDto->getFormattedValue());
    }

    public function testFieldDecimalsAndFractional(): void
    {
        $field = PercentField::new('foo')->setValue(100.1345)->setStoredAsFractional(false)->setNumDecimals(3);
        $fieldDto = $this->configure($field);

        self::assertSame(3, $fieldDto->getCustomOption(PercentField::OPTION_NUM_DECIMALS));
        self::assertSame(3, $fieldDto->getFormTypeOption('scale'));
        self::assertMatchesRegularExpression('/100[.,]135%/', $fieldDto->getFormattedValue());
    }

    public function testFieldSymbolAndFractional(): void
    {
        $field = PercentField::new('foo')->setValue(1.0)->setSymbol(' %')->setStoredAsFractional();
        $fieldDto = $this->configure($field);

        self::assertSame('100 %', $fieldDto->getFormattedValue());
        self::assertSame('fractional', $fieldDto->getFormTypeOption('type'));
    }

    public function testSetNumDecimals(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0.12345)->setStoredAsFractional();
        $field->setNumDecimals(2);
        $fieldDto = $this->configure($field);

        self::assertSame(2, $fieldDto->getCustomOption(PercentField::OPTION_NUM_DECIMALS));
        self::assertSame(2, $fieldDto->getFormTypeOption('scale'));
        self::assertMatchesRegularExpression('/12[.,]35%/', $fieldDto->getFormattedValue());
    }

    public function testSetNumDecimalsThrowsExceptionForNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PercentField::new('foo')->setNumDecimals(-1);
    }

    public function testSetNumDecimalsZero(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0.126)->setStoredAsFractional();
        $field->setNumDecimals(0);
        $fieldDto = $this->configure($field);

        self::assertSame(0, $fieldDto->getCustomOption(PercentField::OPTION_NUM_DECIMALS));
        self::assertSame('13%', $fieldDto->getFormattedValue());
    }

    /**
     * @testWith [0]
     *           [1]
     *           [2]
     *           [3]
     *           [5]
     *           [6]
     *           [4]
     */
    public function testSetRoundingMode(int $mode): void
    {
        $field = PercentField::new('foo');
        $field->setRoundingMode($mode);
        $fieldDto = $this->configure($field);

        self::assertSame($mode, $fieldDto->getCustomOption(PercentField::OPTION_ROUNDING_MODE));
        self::assertSame($mode, $fieldDto->getFormTypeOption('rounding_mode'));
    }

    public function testSetRoundingModeThrowsExceptionForInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PercentField::new('foo')->setRoundingMode(999);
    }

    public function testRoundingBehaviorHalfUp(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0.12345)->setStoredAsFractional();
        $field->setNumDecimals(2);
        $field->setRoundingMode(\NumberFormatter::ROUND_HALFUP);
        $fieldDto = $this->configure($field);

        self::assertMatchesRegularExpression('/12[.,]35%/', $fieldDto->getFormattedValue());
    }

    public function testRoundingBehaviorDown(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0.12349)->setStoredAsFractional();
        $field->setNumDecimals(2);
        $field->setRoundingMode(\NumberFormatter::ROUND_DOWN);
        $fieldDto = $this->configure($field);

        self::assertMatchesRegularExpression('/12[.,]34%/', $fieldDto->getFormattedValue());
    }

    public function testRoundingBehaviorUp(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0.12341)->setStoredAsFractional();
        $field->setNumDecimals(2);
        $field->setRoundingMode(\NumberFormatter::ROUND_UP);
        $fieldDto = $this->configure($field);

        self::assertMatchesRegularExpression('/12[.,]35%/', $fieldDto->getFormattedValue());
    }

    public function testStoredAsFractionalTrue(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0.5)->setStoredAsFractional(true);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(PercentField::OPTION_STORED_AS_FRACTIONAL));
        self::assertSame('fractional', $fieldDto->getFormTypeOption('type'));
        self::assertSame('50%', $fieldDto->getFormattedValue());
    }

    public function testStoredAsFractionalFalse(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(50)->setStoredAsFractional(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(PercentField::OPTION_STORED_AS_FRACTIONAL));
        self::assertSame('integer', $fieldDto->getFormTypeOption('type'));
        self::assertSame('50%', $fieldDto->getFormattedValue());
    }

    public function testSetSymbolCustom(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0.25)->setStoredAsFractional();
        $field->setSymbol(' percent');
        $fieldDto = $this->configure($field);

        self::assertSame(' percent', $fieldDto->getCustomOption(PercentField::OPTION_SYMBOL));
        self::assertSame('25 percent', $fieldDto->getFormattedValue());
    }

    public function testSetSymbolFalse(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0.25)->setStoredAsFractional();
        $field->setSymbol(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(PercentField::OPTION_SYMBOL));
        self::assertSame('25', $fieldDto->getFormattedValue());
    }

    public function testFieldWithNegativeValue(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(-0.15)->setStoredAsFractional();
        $fieldDto = $this->configure($field);

        self::assertSame('-15%', $fieldDto->getFormattedValue());
    }

    public function testFieldWithZeroValue(): void
    {
        $field = PercentField::new('foo');
        $field->setValue(0)->setStoredAsFractional(false);
        $fieldDto = $this->configure($field);

        self::assertSame('0%', $fieldDto->getFormattedValue());
    }

    public function testFormTypeOptionsForFractional(): void
    {
        $field = PercentField::new('foo');
        $field->setStoredAsFractional(true);
        $field->setNumDecimals(2);
        $field->setRoundingMode(\NumberFormatter::ROUND_DOWN);
        $fieldDto = $this->configure($field);

        self::assertSame('fractional', $fieldDto->getFormTypeOption('type'));
        self::assertSame(2, $fieldDto->getFormTypeOption('scale'));
        self::assertSame(\NumberFormatter::ROUND_DOWN, $fieldDto->getFormTypeOption('rounding_mode'));
    }
}
