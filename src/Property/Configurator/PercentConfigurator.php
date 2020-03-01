<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\PercentProperty;

final class PercentConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof PercentProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if (null === $propertyConfig->getValue()) {
            return;
        }

        $scale = $propertyConfig->getCustomOption(PercentProperty::OPTION_NUM_DECIMALS);
        $symbol = $propertyConfig->getCustomOption(PercentProperty::OPTION_SYMBOL);
        $isStoredAsFractional = $propertyConfig->getCustomOption(PercentProperty::OPTION_STORED_AS_FRACTIONAL);
        $value = $propertyConfig->getValue();

        $propertyConfig->setFormattedValue(sprintf('%s%s', $isStoredAsFractional ? 100 * $value : $value, $symbol));

        $propertyConfig->setFormTypeOptionIfNotSet('scale', $scale);
        $propertyConfig->setFormTypeOptionIfNotSet('symbol', $symbol);
        $propertyConfig->setFormTypeOptionIfNotSet('type', $isStoredAsFractional ? 'fractional' : 'integer');
    }
}
