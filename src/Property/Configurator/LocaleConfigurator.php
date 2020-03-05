<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\LocaleProperty;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Locales;

final class LocaleConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof LocaleProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if (null === $localeCode = $propertyConfig->getValue()) {
            return;
        }

        $localeName = $this->getLocaleName($localeCode);
        if (null === $localeName) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the locale code of the "%s" property is not a valid ICU locale code.', $localeCode, $propertyConfig->getName()));
        }

        $propertyConfig->setFormattedValue($localeName);
    }

    private function getLocaleName(string $localeCode): ?string
    {
        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Locales::class)) {
            return Intl::getLocaleBundle()->getLocaleName($localeCode) ?? null;
        }

        try {
            return Locales::getName($localeCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
