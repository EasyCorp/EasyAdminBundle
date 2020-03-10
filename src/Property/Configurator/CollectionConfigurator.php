<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\CollectionProperty;
use function Symfony\Component\String\u;

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

        // collection items range from a simple <input text> to a complex multi-field form
        // the 'entryIsComplex' setting tells if the collection item is so complex that needs a special
        // rendering not applied to simple collection items
        if (null === $propertyConfig->getCustomOption(CollectionProperty::OPTION_ENTRY_IS_COMPLEX)) {
            $definesEntryType = null !== $entryTypeFqcn = $propertyConfig->getCustomOption(CollectionProperty::OPTION_ENTRY_TYPE);
            $isSymfonyCoreFormType = null !== u($entryTypeFqcn ?? '')->indexOf('Symfony\Component\Form\Extension\Core\Type');
            $isComplexEntry = $definesEntryType && !$isSymfonyCoreFormType;

            $propertyConfig->setCustomOption(CollectionProperty::OPTION_ENTRY_IS_COMPLEX, $isComplexEntry);
        }
    }
}
