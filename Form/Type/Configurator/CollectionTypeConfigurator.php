<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator;

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

        // The "delete_empty" option exists as of Sf >= 2.5
        if (class_exists('Symfony\\Component\\Form\\FormErrorIterator')) {
            if (!isset($options['delete_empty'])) {
                $options['delete_empty'] = true;
            }
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        return 'collection' === $type;
    }
}
