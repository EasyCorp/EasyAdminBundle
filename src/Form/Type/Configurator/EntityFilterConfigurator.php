<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\EntityFilter;
use Symfony\Component\Form\FormConfigInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EntityFilterConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig): array
    {
        $entityTypeOptions = $options['value_type_options'] ?? [];

        if (!isset($entityTypeOptions['class'])) {
            $entityTypeOptions['class'] = $metadata['targetEntity'];
        }

        if (!isset($entityTypeOptions['multiple']) && $metadata['associationType'] & ClassMetadata::TO_MANY) {
            $entityTypeOptions['multiple'] = true;
        } elseif (($metadata['associationType'] & ClassMetadata::TO_ONE) && !isset($options['placeholder']) && (!isset($options['required']) || false === $options['required'])) {
            $entityTypeOptions['placeholder'] = 'label.form.empty_value';
        }

        // Supported associations are displayed using advanced JavaScript widgets
        $entityTypeOptions['attr']['data-widget'] = 'select2';

        $options['value_type_options'] = $entityTypeOptions;

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata): bool
    {
        return (EntityFilter::class === $metadata['type']) && ('association' === $metadata['dataType'] ?? null);
    }
}
