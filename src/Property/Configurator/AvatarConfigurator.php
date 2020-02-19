<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\AvatarProperty;

final class AvatarConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof AvatarProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if ('index' === $action) {
            $propertyConfig->setLabel(null);
        }

        if (null === $propertyConfig->getCustomOption(AvatarProperty::OPTION_HEIGHT)) {
            $propertyConfig->setCustomOption(AvatarProperty::OPTION_HEIGHT, 'detail' === $action ? 48 : 28);
        }

        if ($propertyConfig->getCustomOption(AvatarProperty::OPTION_IS_GRAVATAR_EMAIL)) {
            $propertyConfig->setFormattedValue('https://www.gravatar.com/avatar/%s?s=%d&d=mp', md5($propertyConfig->getValue()));
        }

        if (null === $propertyConfig->getFormattedValue()) {
            $propertyConfig->setTemplateName('label/null');
        }
    }
}
