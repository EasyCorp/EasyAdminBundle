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
        if (!$propertyConfig->getCustomOption(BooleanProperty::OPTION_RENDER_AS_SWITCH)) {
            return;
        }

        // see https://symfony.com/blog/new-in-symfony-4-4-bootstrap-custom-switches
        $formTypeOptions = $propertyConfig->getFormTypeOptions();
        $formTypeOptions['label_attr']['class'] = ($formTypeOptions['label_attr']['class'] ?? '') . ' switch-custom';

        $propertyConfig->setFormTypeOptions($formTypeOptions);
    }
}
