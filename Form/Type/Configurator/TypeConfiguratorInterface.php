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
interface TypeConfiguratorInterface
{
    /**
     * Configure the options for this type.
     *
     * @param array $options  Configured options
     * @param array $metadata The EasyAdmin config metadata for this field
     *
     * @return array The array of options to configure
     */
    public function configure(array $options, array $metadata);

    /**
     * Returns true if the type option configurator supports this field.
     *
     * @param string $type     The form type alias or FQCN
     * @param array  $options  Configured options
     * @param array  $metadata The EasyAdmin config metadata for this field
     *
     * @return bool
     */
    public function supports($type, array $options, array $metadata);
}
