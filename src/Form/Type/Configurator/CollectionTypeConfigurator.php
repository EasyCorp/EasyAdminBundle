<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

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
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        if (!isset($options['allow_add'])) {
            $options['allow_add'] = true;
        }

        if (!isset($options['allow_delete'])) {
            $options['allow_delete'] = true;
        }

        if (!isset($options['delete_empty'])) {
            $options['delete_empty'] = true;
        }

        // allow using short form types as the 'entry_type' of the collection
        if (isset($options['entry_type'])) {
            $options['entry_type'] = FormTypeHelper::getTypeClass($options['entry_type']);
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        return \in_array($type, ['collection', CollectionType::class], true);
    }
}
