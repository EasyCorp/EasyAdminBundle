<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\StringProperty;
use EasyCorp\Bundle\EasyAdminBundle\Property\TextProperty;

final class TextConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof TextProperty || $propertyConfig instanceof StringProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $configuredMaxLength = $propertyConfig->getCustomOption(TextProperty::OPTION_MAX_LENGTH);
        $defaultMaxLength = 'detail' === $action ? PHP_INT_MAX : 64;

        $formattedValue = mb_substr($propertyConfig->getValue(), 0, $configuredMaxLength ?? $defaultMaxLength);
        if ($formattedValue !== $propertyConfig->getValue()) {
            $formattedValue .= '…';
        }

        $propertyConfig->setFormattedValue($formattedValue);
    }
}
