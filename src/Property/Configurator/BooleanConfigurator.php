<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\BooleanProperty;

final class BooleanConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof BooleanProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        // TODO: ask someone who knows Symfony forms well how to make this work
        if ($propertyConfig->getCustomOption(BooleanProperty::OPTION_RENDER_AS_SWITCH)) {
            // see https://symfony.com/blog/new-in-symfony-4-4-bootstrap-custom-switches
            // $propertyConfig->setFormTypeOptionIfNotSet('label_attr.class', 'switch-custom');
        }
    }
}
