<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\TextAreaProperty;
use EasyCorp\Bundle\EasyAdminBundle\Property\TextProperty;
use function Symfony\Component\String\u;

final class TextConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof TextProperty || $propertyConfig instanceof TextAreaProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if ($propertyConfig instanceof TextAreaProperty) {
            $propertyConfig->setFormTypeOptionIfNotSet('attr.rows', $propertyConfig->getCustomOption(TextAreaProperty::OPTION_NUM_OF_ROWS));
        }

        if (null === $propertyConfig->getValue()) {
            return;
        }

        $configuredMaxLength = $propertyConfig->getCustomOption(TextAreaProperty::OPTION_MAX_LENGTH);
        $defaultMaxLength = Action::DETAIL === $action ? PHP_INT_MAX : 64;
        $formattedValue = u($propertyConfig->getValue())->truncate($configuredMaxLength ?? $defaultMaxLength, '…');

        $propertyConfig->setFormattedValue($formattedValue);
    }
}
