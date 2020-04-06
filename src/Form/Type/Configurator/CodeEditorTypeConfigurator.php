<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CodeEditorType;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'code_editor'.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CodeEditorTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig): array
    {
        if (isset($metadata['height'])) {
            $options['height'] = $metadata['height'];
        }
        if (isset($metadata['tab_size'])) {
            $options['tab_size'] = $metadata['tab_size'];
        }
        if (isset($metadata['indent_with_tabs'])) {
            $options['indent_with_tabs'] = $metadata['indent_with_tabs'];
        }
        if (isset($metadata['language'])) {
            $options['language'] = $metadata['language'];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata): bool
    {
        return \in_array($type, ['code_editor', CodeEditorType::class], true);
    }
}
