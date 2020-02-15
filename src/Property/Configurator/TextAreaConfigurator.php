<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\TextProperty;
use EasyCorp\Bundle\EasyAdminBundle\Property\TextAreaProperty;

final class TextAreaConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof TextAreaProperty || $propertyConfig instanceof TextProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $configuredMaxLength = $propertyConfig->getCustomOption(TextAreaProperty::OPTION_MAX_LENGTH);
        $defaultMaxLength = 'detail' === $action ? PHP_INT_MAX : 64;

        $formattedValue = mb_substr($propertyConfig->getValue(), 0, $configuredMaxLength ?? $defaultMaxLength);
        if ($formattedValue !== $propertyConfig->getValue()) {
            $formattedValue .= '…';
        }

        $propertyConfig->setFormattedValue($formattedValue);
    }
}
