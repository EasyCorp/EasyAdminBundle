<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\FormTypeHelper;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'collection' and is
 * used to allow adding/removing elements from the collection.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class CollectionTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, FieldDto $fieldDto, FormConfigInterface $parentConfig): array
    {
        if (!isset($formFieldOptions['allow_add'])) {
            $formFieldOptions['allow_add'] = true;
        }

        if (!isset($formFieldOptions['allow_delete'])) {
            $formFieldOptions['allow_delete'] = true;
        }

        if (!isset($formFieldOptions['delete_empty'])) {
            $formFieldOptions['delete_empty'] = true;
        }

        // allow using short form types as the 'entry_type' of the collection
        if (isset($formFieldOptions['entry_type'])) {
            $formFieldOptions['entry_type'] = FormTypeHelper::getTypeClass($formFieldOptions['entry_type']);
        }

        // by default, the numeric auto-increment label of collection items is hidden...
        if (!isset($formFieldOptions['entry_options']['label'])) {
            $formFieldOptions['entry_options']['label'] = false;
        }
        // ...but you can set the 'entry_options.label' option to TRUE to display it
        elseif (true === $formFieldOptions['entry_options']['label']) {
            unset($formFieldOptions['entry_options']['label']);
        }

        return $formFieldOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, FieldDto $fieldDto): bool
    {
        return CollectionType::class === $formTypeFqcn;
    }
}
