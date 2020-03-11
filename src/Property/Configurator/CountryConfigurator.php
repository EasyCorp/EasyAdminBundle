<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\CountryProperty;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

final class CountryConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof CountryProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $propertyConfig->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');

        $formattedValue = $this->getCountryName($propertyConfig->getValue());
        $propertyConfig->setFormattedValue($formattedValue);

        if (null === $propertyConfig->getTextAlign() && false === $propertyConfig->getCustomOption(CountryProperty::OPTION_SHOW_NAME)) {
            $propertyConfig->setTextAlign('center');
        }

        if (null === $formattedValue) {
            $propertyConfig->setTemplateName('label/null');
        }
    }

    private function getCountryName(?string $countryCode): ?string
    {
        if (null === $countryCode) {
            return null;
        }

        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Countries::class)) {
            return Intl::getRegionBundle()->getCountryName($countryCode) ?? null;
        }

        try {
            return Countries::getName($countryCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
