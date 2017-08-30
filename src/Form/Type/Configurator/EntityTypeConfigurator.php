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
 * This configurator is applied to any form field of type 'association' and is
 * used to configure lots of their features (for example whether we should use
 * a JavaScript widget to display their contents).
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EntityTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        if ($metadata['associationType'] & ClassMetadata::TO_MANY) {
            $options['attr']['multiple'] = true;
        }

        // Supported associations are displayed using advanced JavaScript widgets
        $options['attr']['data-widget'] = 'select2';

        // Configure "placeholder" option for entity fields
        if (($metadata['associationType'] & ClassMetadata::TO_ONE)
            && !isset($options[$placeHolderOptionName = $this->getPlaceholderOptionName()])
            && isset($options['required']) && false === $options['required']
        ) {
            $options[$placeHolderOptionName] = 'label.form.empty_value';
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        $isEntityType = in_array($type, array('entity', 'Symfony\Bridge\Doctrine\Form\Type\EntityType'), true);

        return $isEntityType && 'association' === $metadata['type'];
    }

    /**
     * BC for Sf < 2.6
     *
     * The "empty_value" option in the types "choice", "date", "datetime" and "time"
     * was deprecated in 2.6 and replaced by a new option "placeholder".
     *
     * @return string
     */
    private function getPlaceholderOptionName()
    {
        return defined('Symfony\\Component\\Form\\Extension\\Validator\\Constraints\\Form::NOT_SYNCHRONIZED_ERROR')
            ? 'placeholder'
            : 'empty_value';
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\EntityTypeConfigurator', 'JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator\EntityTypeConfigurator', false);
