<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CountryConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return CountryField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');
        $alpha3 = $field->getCustomOption(CountryField::OPTION_ALPHA3);

        $formattedValue = $this->getCountryName($field->getValue(), $alpha3);
        $field->setFormattedValue($formattedValue);
        $field->setFormTypeOption('alpha3', $alpha3);

        if (null === $field->getTextAlign() && false === $field->getCustomOption(CountryField::OPTION_SHOW_NAME)) {
            $field->setTextAlign('center');
        }
    }

    private function getCountryName(?string $countryCode, bool $alpha3): ?string
    {
        if (null === $countryCode) {
            return null;
        }

        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Countries::class)) {
            return Intl::getRegionBundle()->getCountryName($countryCode);
        }

        try {
            if ($alpha3) {
                return Countries::getAlpha3Name($countryCode);
            }

            return Countries::getName($countryCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
