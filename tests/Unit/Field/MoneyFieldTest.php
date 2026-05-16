<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\MoneyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaMoneyType;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use Money\Currency;
use Money\Money;

class MoneyFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new MoneyConfigurator(new IntlFormatter(), static::getContainer()->get('property_accessor'));
    }

    public function testFieldWithoutCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = MoneyField::new('foo')->setValue(100);
        $this->configure($field);
    }

    public function testNullFieldWithoutCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = MoneyField::new('foo')->setValue(null);
        $this->configure($field);
    }

    public function testFieldWithNullValues(): void
    {
        $field = MoneyField::new('foo')->setValue(null)->setCurrency('EUR');
        $fieldDto = $this->configure($field);

        self::assertSame('EUR', $fieldDto->getCustomOption(MoneyField::OPTION_CURRENCY));
    }

    public function testFieldWithWrongCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = MoneyField::new('foo')->setValue(100)->setCurrency('THIS_DOES_NOT_EXIST');
        $this->configure($field);
    }

    public function testFieldWithHardcodedCurrency(): void
    {
        $field = MoneyField::new('foo')->setValue(100)->setCurrency('EUR');
        $fieldDto = $this->configure($field);

        self::assertSame('EUR', $fieldDto->getCustomOption(MoneyField::OPTION_CURRENCY));
        self::assertSame('EUR', $fieldDto->getFormTypeOption('currency'));
    }

    protected function getEntityDto(): EntityDto
    {
        $reflectedClass = new \ReflectionClass(EntityDto::class);
        $entityDto = $reflectedClass->newInstanceWithoutConstructor();
        $primaryKeyValueProperty = $reflectedClass->getProperty('primaryKeyValue');
        $primaryKeyValueProperty->setValue($entityDto, 1);
        $fqcnProperty = $reflectedClass->getProperty('fqcn');
        $fqcnProperty->setValue($entityDto, 'App\Entity\MyEntity');
        $instanceProperty = $reflectedClass->getProperty('entityInstance');
        $instanceProperty->setValue($entityDto, new class {
            public int $id = 1;
            public string $bar = 'USD';
        });

        return $this->entityDto = $entityDto;
    }

    public function testFieldWithPropertyPathCurrency(): void
    {
        $field = MoneyField::new('foo')->setValue(100)->setCurrencyPropertyPath('bar');
        $fieldDto = $this->configure($field);

        self::assertSame('USD', $fieldDto->getFormTypeOption('currency'));
    }

    public function testFieldDecimals(): void
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

    public function testFieldsDefaultsToCents(): void
    {
        $field = MoneyField::new('foo')->setValue(100)->setCurrency('EUR');
        $fieldDto = $this->configure($field);

        self::assertSame('€1.00', $fieldDto->getFormattedValue());
        self::assertSame(100, $fieldDto->getFormTypeOption('divisor'));
    }

    public function testFieldCents(): void
    {
        $field = MoneyField::new('foo')->setValue(100)->setCurrency('EUR');
        $field->setStoredAsCents(false);
        $fieldDto = $this->configure($field);

        self::assertSame('€100.00', $fieldDto->getFormattedValue());
        self::assertSame(1, $fieldDto->getFormTypeOption('divisor'));
    }

    public function testFieldWithCustomDivisor(): void
    {
        $field = MoneyField::new('foo')->setValue(725)->setCurrency('EUR');
        $field->setFormTypeOption('divisor', 10000);
        $fieldDto = $this->configure($field);

        self::assertSame('€0.07', $fieldDto->getFormattedValue());
        self::assertSame(10000, $fieldDto->getFormTypeOption('divisor'));
    }

    public function testFieldWithMoneyObject(): void
    {
        $money = new Money('500', new Currency('EUR'));
        $field = MoneyField::new('foo')->setValue($money);
        $fieldDto = $this->configure($field);

        self::assertSame('€5.00', $fieldDto->getFormattedValue());
        self::assertSame(EaMoneyType::class, $fieldDto->getFormType());
        self::assertSame('EUR', $fieldDto->getFormTypeOption('currency'));
        self::assertTrue($fieldDto->getFormTypeOption('ea_money_object'));
        self::assertSame(100, $fieldDto->getFormTypeOption('divisor'));
    }

    public function testFieldWithMoneyObjectAndExplicitCurrency(): void
    {
        $money = new Money('500', new Currency('EUR'));
        $field = MoneyField::new('foo')->setValue($money)->setCurrency('USD');
        $fieldDto = $this->configure($field);

        self::assertSame('USD', $fieldDto->getFormTypeOption('currency'));
        self::assertSame(EaMoneyType::class, $fieldDto->getFormType());
    }

    public function testFieldWithMoneyObjectNull(): void
    {
        $field = MoneyField::new('foo')->setValue(null)->useMoneyObject()->setCurrency('EUR');
        $fieldDto = $this->configure($field);

        self::assertSame(EaMoneyType::class, $fieldDto->getFormType());
        self::assertSame('EUR', $fieldDto->getFormTypeOption('currency'));
        self::assertTrue($fieldDto->getFormTypeOption('ea_money_object'));
    }

    public function testFieldWithMoneyObjectAndCustomDivisor(): void
    {
        $money = new Money('7500', new Currency('EUR'));
        $field = MoneyField::new('foo')->setValue($money);
        $field->setFormTypeOption('divisor', 1000);
        $fieldDto = $this->configure($field);

        self::assertSame('€7.50', $fieldDto->getFormattedValue());
        self::assertSame(1000, $fieldDto->getFormTypeOption('divisor'));
        self::assertSame(EaMoneyType::class, $fieldDto->getFormType());
    }
}
