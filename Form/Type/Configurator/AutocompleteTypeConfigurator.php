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
        // if the autocomplete field doesn't define the mandatory 'class' option,
        // guess it from the metadata provided by Doctrine
        if (!isset($options['class']) && isset($metadata['targetEntity'])) {
            $options['class'] = $metadata['targetEntity'];
        }

        // settings multiple autocomplete choices by default for OneToMany and ManyToMany association types
        if (!isset($options['multiple']) && $metadata['associationType'] & ClassMetadata::TO_MANY) {
            $options['multiple'] = true;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        return 'easyadmin_autocomplete' === $type;
    }
}
