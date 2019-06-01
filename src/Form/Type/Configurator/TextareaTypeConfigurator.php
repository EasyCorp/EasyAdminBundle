<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'textarea'.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class TextareaTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        // if <textarea> doesn't define its rows, use a default number to make it look good
        $options['attr']['rows'] = 5;

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        $isTextareaField = \in_array($type, ['textarea', TextareaType::class], true);

        return $isTextareaField && !isset($options['attr']['rows']);
    }
}
