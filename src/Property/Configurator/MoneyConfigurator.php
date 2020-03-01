<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Formatter\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Property\MoneyProperty;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class MoneyConfigurator implements PropertyConfiguratorInterface
{
    private $applicationContextProvider;
    private $intlFormatter;

    public function __construct(ApplicationContextProvider $applicationContextProvider, IntlFormatter $intlFormatter, PropertyAccessorInterface $propertyAccessor)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->intlFormatter = $intlFormatter;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof MoneyProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if (null === $propertyConfig->getValue()) {
            return;
        }

        $currencyCode = $this->getCurrency($propertyConfig, $entityDto);
        if (!$this->isValidCurrencyCode($currencyCode)) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the currency of the "%s" money property is not a valid ICU currency code.', $currencyCode, $propertyConfig->getName()));
        }

        $numDecimals = $propertyConfig->getCustomOption(MoneyProperty::OPTION_NUM_DECIMALS);
        $storedAsCents = $propertyConfig->getCustomOption(MoneyProperty::OPTION_STORED_AS_CENTS);
        $amount = $storedAsCents ? $propertyConfig->getValue() / 100 : $propertyConfig->getValue();

        $formattedValue = $this->intlFormatter->formatCurrency($amount, $currencyCode, ['fraction_digit' => $numDecimals]);
        $propertyConfig->setFormattedValue($formattedValue);

        $propertyConfig->setFormTypeOptionIfNotSet('divisor', $storedAsCents ? 100 : 1);
    }

    private function getCurrency(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): string
    {
        if (null !== $currencyCode = $propertyConfig->getCustomOption(MoneyProperty::OPTION_CURRENCY)) {
            return $currencyCode;
        }

        if (null === $currencyPropertyPath = $propertyConfig->getCustomOption(MoneyProperty::OPTION_CURRENCY_PROPERTY_PATH)) {
            throw new \InvalidArgumentException(sprintf('You must define the currency for the "%s" money property.', $propertyConfig->getName()));
        }

        $isPropertyReadable = $this->propertyAccessor->isReadable($entityDto->getInstance(), $currencyPropertyPath);
        if (!$isPropertyReadable) {
            throw new \InvalidArgumentException(sprintf('The "%s" property path used by the "%s" property to get the currency value from the "%s" entity is not readable.', $currencyPropertyPath, $propertyConfig->getName(), $entityDto->getName()));
        }

        if (null === $currencyCode = $this->propertyAccessor->getValue($entityDto->getInstance(), $currencyPropertyPath)) {
            throw new \InvalidArgumentException(sprintf('The currency value for the "%s" property cannot be null, but that\'s the value returned by the "%s" property path applied on the "%s" entity.', $propertyConfig->getName(), $currencyPropertyPath, $entityDto->getName()));
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
