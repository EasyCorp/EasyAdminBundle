<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;

final class CurrencyConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof CurrencyField;
    }

    public function configure(string $action, FieldInterface $field, EntityDto $entityDto): void
    {
        $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');

        if (null === $currencyCode = $field->getValue()) {
            return;
        }

        $currencyName = $this->getCurrencyName($currencyCode);
        if (null === $currencyName) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the currency code of the "%s" field is not a valid ICU currency code.', $currencyCode, $field->getProperty()));
        }

        $currencySymbol = $this->getCurrencySymbol($currencyCode);
        if (null === $currencyName) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the currency code of the "%s" field is not a valid ICU currency code.', $currencyCode, $field->getProperty()));
        }

        $field->setFormattedValue([
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
