<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType;
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
        $supportedTypes = [
            'easyadmin_autocomplete',
            EasyAdminAutocompleteType::class,
        ];

        return \in_array($type, $supportedTypes, true);
    }
}
