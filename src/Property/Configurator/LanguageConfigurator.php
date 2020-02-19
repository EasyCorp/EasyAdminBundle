<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\LanguageProperty;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Languages;

final class LanguageConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof LanguageProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $formattedValue = $this->getLanguageName($propertyConfig->getValue());
        $propertyConfig->setFormattedValue($formattedValue);
    }

    private function getLanguageName(?string $languageCode): ?string
    {
        if (null === $languageCode) {
            return null;
        }

        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Languages::class)) {
            return Intl::getRegionBundle()->getLanguageName($languageCode) ?? null;
        }

        try {
            return Languages::getName($languageCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
