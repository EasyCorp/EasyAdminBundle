<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
use Symfony\Component\Form\FormConfigInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ChoiceFilterTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig): array
    {
        if (isset($metadata['expanded'])) {
            $options['value_type_options']['expanded'] = $metadata['expanded'];
        }
        if (isset($metadata['multiple'])) {
            $options['value_type_options']['multiple'] = $metadata['multiple'];
        }
        if (isset($metadata['choices'])) {
            $options['value_type_options']['choices'] = $metadata['choices'];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata): bool
    {
        return \in_array($type, ['easyadmin.filter.type.choice', ChoiceFilterType::class], true);
    }
}
