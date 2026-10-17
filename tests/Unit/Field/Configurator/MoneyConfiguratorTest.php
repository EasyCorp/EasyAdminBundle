<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Intl\IntlFormatterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\MoneyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class MoneyConfiguratorTest extends TestCase
{
    /**
     * The divisor between a Money object's amount (expressed in the smallest currency
     * unit) and the "main unit" value depends on the fraction digits of the currency;
     * it's not always 100 (e.g. JPY has 0 fraction digits and BHD has 3).
     */
    #[DataProvider('provideMoneyValues')]
    public function testDivisorDependsOnCurrencyFractionDigits(Money $value, int $expectedDivisor, string $expectedAmount): void
    {
        $fieldDto = $this->configureField($value);

        $this->assertSame($expectedDivisor, $fieldDto->getFormTypeOption('divisor'));
        $this->assertSame($expectedAmount, $fieldDto->getFormattedValue());
    }

    public static function provideMoneyValues(): \Generator
    {
        yield 'EUR (2 fraction digits)' => [Money::EUR('1250'), 100, '12.5'];
        yield 'JPY (0 fraction digits)' => [Money::JPY('1000'), 1, '1000'];
        yield 'BHD (3 fraction digits)' => [Money::BHD('12345'), 1000, '12.345'];
    }

    public function testExplicitDivisorIsNotOverridden(): void
    {
        $fieldDto = $this->configureField(Money::JPY('1000'), customDivisor: 100);

        $this->assertSame(100, $fieldDto->getFormTypeOption('divisor'));
    }

    private function configureField(Money $value, ?int $customDivisor = null): FieldDto
    {
        $field = MoneyField::new('price');
        if (null !== $customDivisor) {
            $field->setFormTypeOption('divisor', $customDivisor);
        }

        $fieldDto = $field->getAsDto();
        $fieldDto->setValue($value);

        // return the raw amount so tests can assert the exact value passed to the formatter
        $intlFormatter = $this->createMock(IntlFormatterInterface::class);
        $intlFormatter->method('formatCurrency')->willReturnCallback(static fn ($amount): string => (string) $amount);

        $configurator = new MoneyConfigurator($intlFormatter, PropertyAccess::createPropertyAccessor());
        $entityDto = new EntityDto(\stdClass::class, new ClassMetadata(\stdClass::class));
        $configurator->configure($fieldDto, $entityDto, AdminContext::forTesting());

        return $fieldDto;
    }
}
