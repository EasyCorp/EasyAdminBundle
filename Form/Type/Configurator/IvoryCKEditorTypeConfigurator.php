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

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class IvoryCKEditorTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(array $options, array $metadata)
    {
        // when using a WYSIWYG CKEditor without custom config, apply a better default config
        $options['config']['toolbar'] = array(
            array('name' => 'styles', 'items' => array('Bold', 'Italic', 'Strike', 'Link')),
            array('name' => 'lists', 'items' => array('BulletedList', 'NumberedList', '-', 'Outdent', 'Indent')),
            array('name' => 'clipboard', 'items' => array('Copy', 'Paste', 'PasteFromWord', '-', 'Undo', 'Redo')),
            array('name' => 'advanced', 'items' => array('Source')),
        );

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        $isCkeditorField = in_array($type, array('ckeditor', 'Ivory\\CKEditorBundle\\Form\\Type\\CKEditorType'));

        return $isCkeditorField && !isset($options['config']['toolbar']) && !isset($options['config_name']);
    }
}
