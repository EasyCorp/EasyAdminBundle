<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\CollectionProperty;

final class CollectionConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof CollectionProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $propertyConfig->setFormTypeOption('allow_add', $propertyConfig->getCustomOptions()->get(CollectionProperty::OPTION_ALLOW_ADD));
        $propertyConfig->setFormTypeOption('allow_delete', $propertyConfig->getCustomOptions()->get(CollectionProperty::OPTION_ALLOW_DELETE));

        $propertyConfig->setFormTypeOptionIfNotSet('delete_empty', true);

        if (null !== $formTypeFqcn = $propertyConfig->getCustomOptions()->get(CollectionProperty::OPTION_ENTRY_TYPE)) {
            $propertyConfig->setFormTypeOption('entry_type', $formTypeFqcn);
        }

        // TODO: check why this label (hidden by default) is not working properly
        // (generated values are always the same for all elements)
        $propertyConfig->setFormTypeOptionIfNotSet('entry_options.label', $propertyConfig->getCustomOptions()->get(CollectionProperty::OPTION_SHOW_ENTRY_LABEL));
    }
}
