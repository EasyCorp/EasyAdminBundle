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

use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'fos_ckeditor' available
 * when using the FOSCKEditorBundle. It's used to provide a better default
 * configuration for the WYSIWYG editors created with this bundle.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class FOSCKEditorTypeConfigurator implements TypeConfiguratorInterface
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
        $isFosCkeditorField = in_array($type, ['fos_ckeditor', 'FOS\\CKEditorBundle\\Form\\Type\\CKEditorType'], true);

        return $isFosCkeditorField && !isset($options['config']['toolbar']) && !isset($options['config_name']);
    }
}
