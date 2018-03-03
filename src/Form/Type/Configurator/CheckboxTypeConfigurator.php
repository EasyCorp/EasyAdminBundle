<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
        return \in_array($type, ['checkbox', CheckboxType::class], true);
    }
}
