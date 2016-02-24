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
 * This configurator is applied to any form field of type 'checkbox' and is used
 * to decide whether the field should be required or not.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class CheckboxTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        // If no value is provided explicitly for the "required" option, assume the checkbox is not required.
        // Otherwise, HTML5 validation will prevent the form from being submitted.
        if (!isset($options['required'])) {
            $options['required'] = false;
        }

        $options['label'] = $metadata['label'];

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        return 'checkbox' === $type;
    }
}
