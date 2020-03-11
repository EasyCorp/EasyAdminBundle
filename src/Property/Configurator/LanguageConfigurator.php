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
        $propertyConfig->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');

        if (null === $languageCode = $propertyConfig->getValue()) {
            return;
        }

        $languageName = $this->getLanguageName($languageCode);
        if (null === $languageName) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the language code of the "%s" property is not a valid ICU language code.', $languageCode, $propertyConfig->getName()));
        }

        $propertyConfig->setFormattedValue($languageName);
    }

    private function getLanguageName(string $languageCode): ?string
    {
        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Languages::class)) {
            return Intl::getLanguageBundle()->getLanguageName($languageCode) ?? null;
        }

        try {
            return Languages::getName($languageCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
