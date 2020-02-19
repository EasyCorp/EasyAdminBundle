<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

final class CommonPostConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        // this configurator applies to all kinds of properties
        return true;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $formattedValue = $this->buildFormattedValueOption($propertyConfig->getFormattedValue(), $propertyConfig, $entityDto);
        $propertyConfig->setFormattedValue($formattedValue);
    }

    private function buildFormattedValueOption($value, PropertyConfigInterface $propertyConfig, EntityDto $entityDto)
    {
        if (null === $callable = $propertyConfig->getFormatValueCallable()) {
            return $value;
        }

        return \call_user_func($callable, $value, $entityDto->getInstance());
    }
}
