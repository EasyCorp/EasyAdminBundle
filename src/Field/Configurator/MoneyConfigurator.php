<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Intl\IntlFormatterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaMoneyType;
use Money\Money;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class MoneyConfigurator implements FieldConfiguratorInterface
{
    public const DEFAULT_DIVISOR = 100;

    public function __construct(private readonly IntlFormatterInterface $intlFormatter, private readonly PropertyAccessorInterface $propertyAccessor)
    {
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return MoneyField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if ($this->shouldUseMoneyObject($field)) {
            $this->configureForMoneyObject($field, $entityDto);
        } else {
            $this->configureForScalarValue($field, $entityDto);
        }
    }

    private function shouldUseMoneyObject(FieldDto $field): bool
    {
        if (null !== $configOption = $field->getCustomOption(MoneyField::OPTION_USE_MONEY_OBJECT)) {
            return $configOption;
        }

        return $field->getValue() instanceof Money;
    }

    private function configureForMoneyObject(FieldDto $field, EntityDto $entityDto): void
    {
        $value = $field->getValue();

        // determine currency: explicit setCurrency() takes priority, then Money object's currency
        $currencyCode = $field->getCustomOption(MoneyField::OPTION_CURRENCY);
        if (null === $currencyCode && null !== $currencyPropertyPath = $field->getCustomOption(MoneyField::OPTION_CURRENCY_PROPERTY_PATH)) {
            $entityInstance = $entityDto->getInstance();
            if (null !== $entityInstance && $this->propertyAccessor->isReadable($entityInstance, $currencyPropertyPath)) {
                $currencyCode = $this->propertyAccessor->getValue($entityInstance, $currencyPropertyPath);
            }
        }
        if (null === $currencyCode && $value instanceof Money) {
            $currencyCode = $value->getCurrency()->getCode();
        }

        $field->setFormType(EaMoneyType::class);
        $field->setFormTypeOption('currency', $currencyCode);
        $field->setFormTypeOption('ea_money_object', true);

        $numDecimals = $field->getCustomOption(MoneyField::OPTION_NUM_DECIMALS);
        $field->setFormTypeOption('scale', $numDecimals);

        // Money objects store amounts in the smallest unit of their currency; the scale of that
        // unit depends on the currency (e.g. 2 fraction digits for USD/EUR, 0 for JPY, 3 for BHD)
        $divisor = null !== $currencyCode && CurrencyField::CURRENCY_NONE !== $currencyCode
            ? 10 ** Currencies::getFractionDigits($currencyCode)
            : self::DEFAULT_DIVISOR;
        $field->setFormTypeOptionIfNotSet('divisor', $divisor);

        if (null === $value) {
            return;
        }

        $divisor = $field->getFormTypeOption('divisor');
        $amount = (int) $value->getAmount() / $divisor;
        $field->setFormattedValue($this->formatAmount($amount, $currencyCode, $numDecimals));
    }

    private function configureForScalarValue(FieldDto $field, EntityDto $entityDto): void
    {
        $currencyCode = $this->getCurrency($field, $entityDto);
        $field->setFormTypeOption('currency', $currencyCode);

        $numDecimals = $field->getCustomOption(MoneyField::OPTION_NUM_DECIMALS);
        $field->setFormTypeOption('scale', $numDecimals);

        $isStoredAsCents = true === $field->getCustomOption(MoneyField::OPTION_STORED_AS_CENTS);
        $field->setFormTypeOptionIfNotSet('divisor', $isStoredAsCents ? self::DEFAULT_DIVISOR : 1);

        if (null === $field->getValue()) {
            return;
        }

        $divisor = $field->getFormTypeOption('divisor');
        $amount = $field->getValue() / $divisor;
        $field->setFormattedValue($this->formatAmount($amount, $currencyCode, $numDecimals));
    }

    private function formatAmount(int|float $amount, string $currencyCode, int $numDecimals): string
    {
        if (CurrencyField::CURRENCY_NONE === $currencyCode || Currencies::exists($currencyCode)) {
            return $this->intlFormatter->formatCurrency($amount, $currencyCode, ['fraction_digit' => $numDecimals]);
        }

        // the currency comes from the entity data (setCurrency() rejects unknown codes), which can be written
        // outside of the backend without validation; ICU can't format some of those codes or alters them
        // (e.g. it truncates them to three letters), so display the amount followed by the stored code
        return sprintf('%s %s', $this->intlFormatter->formatNumber($amount, ['fraction_digit' => $numDecimals]), $currencyCode);
    }

    private function getCurrency(FieldDto $field, EntityDto $entityDto): ?string
    {
        if (null !== $currencyCode = $field->getCustomOption(MoneyField::OPTION_CURRENCY)) {
            return $currencyCode;
        }

        if (null === $currencyPropertyPath = $field->getCustomOption(MoneyField::OPTION_CURRENCY_PROPERTY_PATH)) {
            throw new \InvalidArgumentException(sprintf('You must define the currency for the "%s" money field.', $field->getProperty()));
        }

        $entityInstance = $entityDto->getInstance();
        $isPropertyReadable = (null !== $entityInstance) && $this->propertyAccessor->isReadable($entityInstance, $currencyPropertyPath);

        if ($isPropertyReadable && (null !== $currencyCode = $this->propertyAccessor->getValue($entityInstance, $currencyPropertyPath))) {
            return $currencyCode;
        }

        if (null === $field->getValue()) {
            return null;
        }

        if (!$isPropertyReadable) {
            throw new \InvalidArgumentException(sprintf('The "%s" field path used by the "%s" field to get the currency value from the "%s" entity is not readable.', $currencyPropertyPath, $field->getProperty(), $entityDto->getName()));
        }

        throw new \InvalidArgumentException(sprintf('The currency value for the "%s" field cannot be null, but that\'s the value returned by the "%s" field path applied on the "%s" entity.', $field->getProperty(), $currencyPropertyPath, $entityDto->getName()));
    }
}
