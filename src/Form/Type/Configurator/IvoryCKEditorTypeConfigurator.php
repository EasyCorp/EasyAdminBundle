<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'ckeditor' available
 * when using the IvoryCKEditorBundle. It's used to provide a better default
 * configuration for the WYSIWYG editors created with this bundle.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class IvoryCKEditorTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        // when the IvoryCKEditor doesn't define the toolbar to use, EasyAdmin uses a simple toolbar
        $options['config']['toolbar'] = [
            ['name' => 'styles', 'items' => ['Bold', 'Italic', 'Strike', 'Link']],
            ['name' => 'lists', 'items' => ['BulletedList', 'NumberedList', '-', 'Outdent', 'Indent']],
            ['name' => 'clipboard', 'items' => ['Copy', 'Paste', 'PasteFromWord', '-', 'Undo', 'Redo']],
            ['name' => 'advanced', 'items' => ['Source']],
        ];

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        $isCkeditorField = \in_array($type, ['ckeditor', 'Ivory\\CKEditorBundle\\Form\\Type\\CKEditorType'], true);

        return $isCkeditorField && !isset($options['config']['toolbar']) && !isset($options['config_name']);
    }
}
