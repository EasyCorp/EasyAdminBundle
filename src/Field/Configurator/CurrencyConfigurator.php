<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CurrencyConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return CurrencyField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOptionIfNotSet('attr.data-ea-widget', 'ea-autocomplete');
        // currency names passed to the form are already translated, so don't translate them again in the template
        $field->setFormTypeOptionIfNotSet('choice_translation_domain', false);

        if (null === $currencyCode = $field->getValue()) {
            return;
        }

        if (CurrencyField::CURRENCY_NONE === $currencyCode) {
            // the ISO 4217 "no currency" code has no ICU name/symbol; use the generic currency sign
            $field->setFormattedValue(['name' => '¤', 'symbol' => '¤']);

            return;
        }

        $currencyName = $this->getCurrencyName($currencyCode);
        $currencySymbol = $this->getCurrencySymbol($currencyCode);
        if (null === $currencyName || null === $currencySymbol) {
            // the stored value can be written outside of the backend without validation, so an
            // unknown code must not break the pages that display it (same as in CountryField)
            $field->setFormattedValue([
                'name' => sprintf('Unknown "%s" currency code', $currencyCode),
                'symbol' => $currencyCode,
            ]);

            return;
        }

        $field->setFormattedValue([
            'name' => $currencyName,
            'symbol' => $currencySymbol,
        ]);
    }

    private function getCurrencyName(string $currencyCode): ?string
    {
        try {
            return Currencies::getName($currencyCode);
        } catch (MissingResourceException) {
            return null;
        }
    }

    private function getCurrencySymbol(string $currencyCode): ?string
    {
        try {
            return Currencies::getSymbol($currencyCode);
        } catch (MissingResourceException) {
            return null;
        }
    }
}
