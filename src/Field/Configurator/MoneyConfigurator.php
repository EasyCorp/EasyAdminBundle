<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class MoneyConfigurator implements FieldConfiguratorInterface
{
    private $adminContextProvider;
    private $intlFormatter;

    public function __construct(AdminContextProvider $adminContextProvider, IntlFormatter $intlFormatter, PropertyAccessorInterface $propertyAccessor)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->intlFormatter = $intlFormatter;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof MoneyField;
    }

    public function configure(string $action, FieldInterface $field, EntityDto $entityDto): void
    {
        if (null === $field->getValue()) {
            return;
        }

        $currencyCode = $this->getCurrency($field, $entityDto);
        if (!$this->isValidCurrencyCode($currencyCode)) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the currency of the "%s" money field is not a valid ICU currency code.', $currencyCode, $field->getProperty()));
        }

        $numDecimals = $field->getCustomOption(MoneyField::OPTION_NUM_DECIMALS);
        $storedAsCents = $field->getCustomOption(MoneyField::OPTION_STORED_AS_CENTS);
        $amount = $storedAsCents ? $field->getValue() / 100 : $field->getValue();

        $formattedValue = $this->intlFormatter->formatCurrency($amount, $currencyCode, ['fraction_digit' => $numDecimals]);
        $field->setFormattedValue($formattedValue);

        $field->setFormTypeOptionIfNotSet('divisor', $storedAsCents ? 100 : 1);
    }

    private function getCurrency(FieldInterface $field, EntityDto $entityDto): string
    {
        if (null !== $currencyCode = $field->getCustomOption(MoneyField::OPTION_CURRENCY)) {
            return $currencyCode;
        }

        if (null === $currencyPropertyPath = $field->getCustomOption(MoneyField::OPTION_CURRENCY_PROPERTY_PATH)) {
            throw new \InvalidArgumentException(sprintf('You must define the currency for the "%s" money field.', $field->getProperty()));
        }

        $isPropertyReadable = $this->propertyAccessor->isReadable($entityDto->getInstance(), $currencyPropertyPath);
        if (!$isPropertyReadable) {
            throw new \InvalidArgumentException(sprintf('The "%s" field path used by the "%s" field to get the currency value from the "%s" entity is not readable.', $currencyPropertyPath, $field->getProperty(), $entityDto->getName()));
        }

        if (null === $currencyCode = $this->propertyAccessor->getValue($entityDto->getInstance(), $currencyPropertyPath)) {
            throw new \InvalidArgumentException(sprintf('The currency value for the "%s" field cannot be null, but that\'s the value returned by the "%s" field path applied on the "%s" entity.', $field->getProperty(), $currencyPropertyPath, $entityDto->getName()));
        }

        return $currencyCode;
    }

    private function isValidCurrencyCode(string $currencyCode): bool
    {
        if (!class_exists(Currencies::class)) {
            return !empty(Intl::getCurrencyBundle()->getCurrencyName($currencyCode));
        }

        return Currencies::exists($currencyCode);
    }
}
