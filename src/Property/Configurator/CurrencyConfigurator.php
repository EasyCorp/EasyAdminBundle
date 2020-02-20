<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\CurrencyProperty;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;

final class CurrencyConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof CurrencyProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if (null === $currencyCode = $propertyConfig->getValue()) {
            return;
        }

        $currencyName = $this->getCurrencyName($currencyCode);
        if (null === $currencyName) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the currency code of the "%s" property is not a valid ICU currency code.', $currencyCode, $propertyConfig->getName()));
        }

        $currencySymbol = $this->getCurrencySymbol($currencyCode);
        if (null === $currencyName) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the currency code of the "%s" property is not a valid ICU currency code.', $currencyCode, $propertyConfig->getName()));
        }

        $propertyConfig->setFormattedValue([
            'name' => $currencyName,
            'symbol' => $currencySymbol,
        ]);
    }

    private function getCurrencyName(string $currencyCode): ?string
    {
        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Currencies::class)) {
            return Intl::getCurrencyBundle()->getCurrencyName($currencyCode) ?? null;
        }

        try {
            return Currencies::getName($currencyCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }

    private function getCurrencySymbol(string $currencyCode): ?string
    {
        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Currencies::class)) {
            return Intl::getCurrencyBundle()->getCurrencySymbol($currencyCode) ?? null;
        }

        try {
            return Currencies::getSymbol($currencyCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
