<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'easyadmin_autocomplete'
 * and is used to configure the class of the autocompleted entity.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class AutocompleteTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        // by default, guess the mandatory 'class' option from the Doctrine metadata
        if (!isset($options['class']) && isset($metadata['targetEntity'])) {
            $options['class'] = $metadata['targetEntity'];
        }

        // by default, allow to autocomplete multiple values for OneToMany and ManyToMany associations
        if (!isset($options['multiple']) && isset($metadata['associationType']) && $metadata['associationType'] & ClassMetadata::TO_MANY) {
            $options['multiple'] = true;
        }

        if (null !== $metadata['label'] && !isset($options['label'])) {
            $options['label'] = $metadata['label'];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        $supportedTypes = array(
            'easyadmin_autocomplete',
            'EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType',
        );

        return in_array($type, $supportedTypes, true);
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\AutocompleteTypeConfigurator', 'JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator\AutocompleteTypeConfigurator', false);
